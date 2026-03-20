<?php
require_once('../config.php');
Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}

	/**
	 * Determine whether the given username already exists.
	 *
	 * @param string $username
	 * @param int    $excludeId optional id to exclude (for updates)
	 * @return bool
	 */
	private function usernameExists($username, $excludeId = 0){
		if($excludeId>0){
			$res = $this->queryPrepared("SELECT 1 FROM `entity_list` WHERE username = ? AND id != ? AND `entity_type` = 'User'", 'si', [$username, $excludeId]);
		}else{
			$res = $this->queryPrepared("SELECT 1 FROM `entity_list` WHERE username = ? AND `entity_type` = 'User'", 's', [$username]);
		}
		return $res && $res->num_rows > 0;
	}

	/**
	 * Convert POST data into an array of parameters for insert/update.
	 */
	private function prepareUserParams($post){
		$params = [];
		foreach(['firstname','middlename','lastname','username','type'] as $key){
			if(isset($post[$key])) $params[$key] = $post[$key];
		}
		if(!empty($post['password'])){
			$params['password'] = md5($post['password']);
		}
		return $params;
	}

	/**
	 * Persist metadata entries for a user; existing rows are deleted first.
	 * If saving fails and this was a new user, the caller should roll back the user
	 * record as well.
	 */
	private function saveUserMeta($userId, $metaRows, $newUser){
		$this->queryPrepared("DELETE FROM user_meta WHERE user_id=?", 'i', [$userId]);
		if(empty($metaRows)) return true;
		$stmt = $this->conn->prepare("INSERT INTO user_meta (user_id,meta_field,meta_value) VALUES (?,?,?)");
		foreach($metaRows as $row){
			$stmt->bind_param('iss', $userId, $row['field'], $row['value']);
			if(!$stmt->execute()){
				if($newUser){
					$this->queryPrepared("DELETE FROM `entity_list` WHERE id=? AND `entity_type` = 'User'",'i',[$userId]);
				}
				$stmt->close();
				return false;
			}
		}
		$stmt->close();
		return true;
	}

	/**
	 * Handle avatar upload, update DB and session when necessary.
	 */
	private function processAvatarUpload($userId, &$resp){
		if(!isset($_FILES['img']) || $_FILES['img']['tmp_name']=='') return;
		$fname = 'uploads/avatar-'.$userId.'.png';
		$dir_path = base_app.$fname;
		$upload = $_FILES['img']['tmp_name'];
		$type = mime_content_type($upload);
		$allowed = ['image/png','image/jpeg'];
		if(!in_array($type,$allowed)){
			$resp['msg'].=" But Image failed to upload due to invalid file type.";
			return;
		}
		// resize to 200x200
		list($width,$height) = getimagesize($upload);
		$new_width = $new_height = 200;
		$t_image = imagecreatetruecolor($new_width,$new_height);
		imagealphablending($t_image,false);
		imagesavealpha($t_image,true);
		$gdImg = ($type=='image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
		imagecopyresampled($t_image,$gdImg,0,0,0,0,$new_width,$new_height,$width,$height);
		if($gdImg){
			if(is_file($dir_path)) unlink($dir_path);
			$uploaded_img = imagepng($t_image,$dir_path);
			imagedestroy($gdImg);
			imagedestroy($t_image);
		} else {
			$resp['msg'].=" But Image failed to upload due to unknown reason.";
		}
		if(!empty($uploaded_img)){
			$this->queryPrepared("UPDATE `entity_list` SET avatar=CONCAT(?,'?v=',UNIX_TIMESTAMP()) WHERE id= ? AND `entity_type` = 'User'",'s i',[$fname,$userId]);
			if($userId==$this->settings->userdata('id')){
				$this->settings->set_userdata('avatar',$fname);
			}
		}
	}
	public function save_users(){
		extract($_POST);
		$oid = $id;
		$data = '';
		if(isset($oldpassword)){
			if(md5($oldpassword) != $this->settings->userdata('password')){
				return 4;
			}
		}
		// check for existing username using prepared statement
		if($id>0){
			$chkStmt = $this->queryPrepared("SELECT id FROM `entity_list` WHERE username = ? AND id != ? AND `entity_type` = 'User'", 'si', [$username, $id]);
		}else{
			$chkStmt = $this->queryPrepared("SELECT id FROM `entity_list` WHERE username = ? AND `entity_type` = 'User'", 's', [$username]);
		}
		$chk = $chkStmt ? $chkStmt->num_rows : 0;
		if($chk > 0){
			return 3;
			exit;
		}
		foreach($_POST as $k => $v){
			if(in_array($k,array('firstname','middlename','lastname','username','type'))){
				if(!empty($data)) $data .=" , ";
				$data .= " {$k} = '{$v}' ";
			}
		}
		if(!empty($password)){
			$password = md5($password);
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}

		if(empty($id)){
			// insert user using prepared statement
			$display_name = trim("$firstname $lastname");
			$middlename = isset($middlename) ? $middlename : '';
			$sql = "INSERT INTO `entity_list` (entity_type, display_name, firstname, middlename, lastname, username, role_id".(isset($password)?", password":"").") VALUES ('User', ?,?,?,?,?,?".(isset($password)?",?":"").")";
			$params = [$display_name, $firstname,$middlename,$lastname,$username,$type];
			if(isset($password)) $params[] = md5($password);
			$result = $this->queryPrepared($sql, str_repeat('s', count($params)), $params);
			if($result){
				$id = $this->conn->insert_id;
				$this->settings->set_flashdata('success','User Details successfully saved.');
				$resp['status'] = 1;

                // Search Index Update for new user
                $display_name = trim("$firstname $lastname");
                $search_data = implode(' ', [$display_name, $username]);
                $this->update_search_index('entity_list', $id, 'user', $display_name, $username, $search_data, 0, null, "system/users/view&id=$id", "system/users/manage_user&id=$id");
			}else{
				$resp['status'] = 2;
			}
		}else{
			// update existing record
			$display_name = trim("$firstname $lastname");
			$middlename = isset($middlename) ? $middlename : '';
			$sql = "UPDATE `entity_list` SET display_name=?, firstname=?, middlename=?, lastname=?, username=?, role_id=?".
				(!empty($password)?", password=?":"")." WHERE id=? AND entity_type = 'User'";
			$params = [$display_name, $firstname,$middlename,$lastname,$username,$type];
			if(!empty($password)) $params[] = md5($password);
			$params[] = $id;
			$result = $this->queryPrepared($sql, str_repeat('s', count($params)-1).'i', $params);
			if($result){
				$this->settings->set_flashdata('success','User Details successfully updated.');
				if($id == $this->settings->userdata('id')){
					foreach($_POST as $k => $v){
						if($k != 'id'){
							$this->settings->set_userdata($k,$v);
						}
					}
				}
				$resp['status'] = 1;
                
                // Search Index Update for updated user
                $display_name = trim("$firstname $lastname");
                $search_data = implode(' ', [$display_name, $username]);
                $this->update_search_index('entity_list', $id, 'user', $display_name, $username, $search_data, 0, null, "system/users/view&id=$id", "system/users/manage_user&id=$id");
			}else{
				$resp['status'] = 2;
			}
		}
		if($resp['status'] == 1){
			$data="";
			foreach($_POST as $k => $v){
				if(!in_array($k,array('id','firstname','middlename','lastname','username','password','type','oldpassword'))){
					if(!empty($data)) $data .=", ";
					$v = $this->conn->real_escape_string($v);
					$data .= "('{$id}','{$k}', '{$v}')";
				}
			}
			if(!empty($data)){
				// delete and reinsert meta using prepared statements
				$this->queryPrepared("DELETE FROM user_meta WHERE user_id=?", 'i', [$id]);
				$stmt = $this->conn->prepare("INSERT INTO user_meta (user_id,meta_field,meta_value) VALUES (?,?,?)");
				$rows = explode('),(', trim($data,'()'));
				foreach($rows as $row){
					list($uid,$field,$val) = explode(',', $row);
					$stmt->bind_param('iss', $id, $field, $val);
					if(!$stmt->execute()){
						$resp['status'] = 2;
						if(empty($oid)){
							$this->queryPrepared("DELETE FROM `entity_list` WHERE id=? AND entity_type = 'User'", 'i', [$id]);
						}
						break;
					}
				}
				$stmt->close();
			}
		}
		
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = 'uploads/avatar-'.$id.'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['img']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$resp['msg'].=" But Image failed to upload due to invalid file type.";
			}else{
				$new_height = 200; 
				$new_width = 200; 
		
				list($width, $height) = getimagesize($upload);
				$t_image = imagecreatetruecolor($new_width, $new_height);
				imagealphablending( $t_image, false );
				imagesavealpha( $t_image, true );
				$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
				imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				if($gdImg){
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
				}else{
				$resp['msg'].=" But Image failed to upload due to unkown reason.";
				}
			}
			if(isset($uploaded_img)){
				$this->conn->query("UPDATE `entity_list` set `avatar` = CONCAT('{$fname}','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}' AND entity_type = 'User'");
				if($id == $this->settings->userdata('id')){
						$this->settings->set_userdata('avatar',$fname);
				}
			}
		}
		if(isset($resp['msg']))
		$this->settings->set_flashdata('success',$resp['msg']);
		return  $resp['status'];
	}
	public function delete_users(){
		extract($_POST);
		// fetch avatar with prepared statement
		$avatar = null;
		if($res=$this->queryPrepared("SELECT avatar FROM `entity_list` WHERE id=? AND entity_type = 'User'", 'i', [$id])){
			$row = $res->fetch_array();
			$avatar = $row['avatar'];
		}
		$qry = $this->queryPrepared("DELETE FROM `entity_list` WHERE id=? AND entity_type = 'User'", 'i', [$id]);
		if($qry){
			$avatar = explode("?",$avatar)[0];
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			if(is_file(base_app.$avatar))
				unlink(base_app.$avatar);
			$resp['status'] = 'success';
            $this->delete_from_index('entity_list', $id);
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	
	public function save_susers(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','password'))){
				if(!empty($data)) $data .= ", ";
				$data .= " `{$k}` = '{$v}' ";
			}
		}

			if(!empty($password))
			$data .= ", `password` = '".md5($password)."' ";
		
			if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
				$fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
				$move = move_uploaded_file($_FILES['img']['tmp_name'],'../'. $fname);
				if($move){
					$data .=" , avatar = '{$fname}' ";
					if(isset($_SESSION['userdata']['avatar']) && is_file('../'.$_SESSION['userdata']['avatar']))
						unlink('../'.$_SESSION['userdata']['avatar']);
				}
			}
			$sql = "UPDATE students set {$data} where id = $id";
			$save = $this->conn->query($sql);

			if($save){
			$this->settings->set_flashdata('success','User Details successfully updated.');
			foreach($_POST as $k => $v){
				if(!in_array($k,array('id','password'))){
					if(!empty($data)) $data .=" , ";
					$this->settings->set_userdata($k,$v);
				}
			}
			if(isset($fname) && isset($move))
			$this->settings->set_userdata('avatar',$fname);
			return 1;
			}else{
				$resp['error'] = $sql;
				return json_encode($resp);
			}

	} 
	
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
	break;
	case 'fsave':
		echo $users->save_fusers();
	break;
	case 'ssave':
		echo $users->save_susers();
	break;
	case 'delete':
		echo $users->delete_users();
	break;
	default:
		// echo $sysset->index();
		break;
}