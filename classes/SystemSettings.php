<?php
if(!class_exists('DBConnection')){
	require_once('../config.php');
	require_once('DBConnection.php');
}
class SystemSettings extends DBConnection{
	public function __construct(){
		parent::__construct();
	}
	function check_connection(){
		return($this->conn);
	}
	/**
	 * Return all system_info rows as an associative array.
	 *
	 * This avoids repeating the SELECT statement in multiple places.
	 */
	function getAllSystemInfo(){
		$result = [];
		if($res = $this->queryPrepared("SELECT meta_field,meta_value FROM system_info")){
			while($row = $res->fetch_assoc()){
				$result[$row['meta_field']] = $row['meta_value'];
			}
		}
		return $result;
	}

	function load_system_info(){
		$_SESSION['system_info'] = $this->getAllSystemInfo();
	}
	function update_system_info(){
		// refresh session cache from database
		$_SESSION['system_info'] = $this->getAllSystemInfo();
		return true;
	}
	function update_settings_info(){
		$data = "";
		// prepare statements once
		$upd = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
		$ins = $this->conn->prepare("INSERT INTO system_info (meta_value,meta_field) VALUES (?,?)");
		foreach ($_POST as $key => $value) {
			if($key === "content") continue;
			if(isset($_SESSION['system_info'][$key])){
				$value = str_replace("'", "&apos;", $value);
				$upd->bind_param('ss', $value, $key);
				$upd->execute();
			}else{
				$ins->bind_param('ss', $value, $key);
				$ins->execute();
			}
		}
		$upd->close();
		$ins->close();
		if(isset($_POST['content']))
		foreach($_POST['content'] as $k => $v){
			file_put_contents("../{$k}.html",$v);

		}
		
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = 'uploads/logo-'.(time()).'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['img']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$_SESSION['flashdata']['error'] = "But Image failed to upload due to invalid file type.";
			}else{
				if(!function_exists('imagecreatetruecolor')){
					$_SESSION['flashdata']['error'] = "GD Library is not enabled in your PHP installation. Please enable it to upload images.";
				}else{
					$new_height = 200; 
					$new_width = 200; 
			
					list($width, $height) = getimagesize($upload);
					$t_image = imagecreatetruecolor($new_width, $new_height);
					imagealphablending( $t_image, false );
					imagesavealpha( $t_image, true );
					$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
					if($gdImg){
						imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
					}else{
						$_SESSION['flashdata']['error'] = "But Image failed to upload due to unknown reason.";
					}
				}
			}
			if(isset($uploaded_img) && $uploaded_img == true){
				if(isset($_SESSION['system_info']['logo'])){
					$qry = $this->conn->query("UPDATE system_info set meta_value = '{$fname}' where meta_field = 'logo' ");
					if(is_file(base_app.$_SESSION['system_info']['logo'])) unlink(base_app.$_SESSION['system_info']['logo']);
				}else{
					$qry = $this->conn->query("INSERT into system_info set meta_value = '{$fname}',meta_field = 'logo' ");
				}
				unset($uploaded_img);
			}
		}
		if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
			$fname = 'uploads/cover-'.time().'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['cover']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$_SESSION['flashdata']['error'] = "But Image failed to upload due to invalid file type.";
			}else{
				if(!function_exists('imagecreatetruecolor')){
					$_SESSION['flashdata']['error'] = "GD Library is not enabled in your PHP installation. Please enable it to upload images.";
				}else{
					$new_height = 720; 
					$new_width = 1280; 
			
					list($width, $height) = getimagesize($upload);
					$t_image = imagecreatetruecolor($new_width, $new_height);
					$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
					if($gdImg){
						imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
					}else{
						$_SESSION['flashdata']['error'] = "But Image failed to upload due to unknown reason.";
					}
				}
			}
			if(isset($uploaded_img) && $uploaded_img == true){
				if(isset($_SESSION['system_info']['cover'])){
					$qry = $this->conn->query("UPDATE system_info set meta_value = '{$fname}' where meta_field = 'cover' ");
					if(is_file(base_app.$_SESSION['system_info']['cover'])) unlink(base_app.$_SESSION['system_info']['cover']);
				}else{
					$qry = $this->conn->query("INSERT into system_info set meta_value = '{$fname}',meta_field = 'cover' ");
				}
				unset($uploaded_img);
			}
		}
		
		$update = $this->update_system_info();
		$flash = $this->set_flashdata('success','System Info Successfully Updated.');
		if($update && $flash){
			// var_dump($_SESSION);
			return true;
		}
	}
	function set_userdata($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['userdata'][$field]= $value;
		}
	}
	function userdata($field = ''){
		if(!empty($field)){
			if(isset($_SESSION['userdata'][$field]))
				return $_SESSION['userdata'][$field];
			else
				return null;
		}else{
			return false;
		}
	}
	function set_flashdata($flash='',$value=''){
		if(!empty($flash) && !empty($value)){
			$_SESSION['flashdata'][$flash]= $value;
		return true;
		}
	}
	function chk_flashdata($flash = ''){
		if(isset($_SESSION['flashdata'][$flash])){
			return true;
		}else{
			return false;
		}
	}
	function flashdata($flash = ''){
		if(!empty($flash)){
			$_tmp = $_SESSION['flashdata'][$flash];
			unset($_SESSION['flashdata']);
			return $_tmp;
		}else{
			return false;
		}
	}
	function sess_des(){
		if(isset($_SESSION['userdata'])){
				unset($_SESSION['userdata']);
			return true;
		}
			return true;
	}
	function info($field=''){
		if(!empty($field)){
			if(isset($_SESSION['system_info'][$field]))
				return $_SESSION['system_info'][$field];
			else
				return false;
		}else{
			return false;
		}
	}
	function set_info($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['system_info'][$field] = $value;
		}
	}
    function log_action($user_id, $action, $module, $ref_id, $old_data = '', $new_data = ''){
		$sql = "INSERT INTO `system_logs` (`user_id`, `action`, `module`, `ref_id`, `old_data`, `new_data`) VALUES (?, ?, ?, ?, ?, ?)";
		$this->queryPrepared($sql, 'isssss', [$user_id, $action, $module, $ref_id, $old_data, $new_data]);
	}
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'update_settings':
		echo $sysset->update_settings_info();
		break;
	default:
		// echo $sysset->index();
		break;
}
?>