<?php
require_once __DIR__ . '/../config.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		ini_set('display_errors', 1);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	public function login(){
		extract($_POST);

		$stmt = $this->conn->prepare("SELECT id, firstname, lastname, username, type, avatar FROM `users` WHERE username = ? AND password = md5(?)");
		$stmt->bind_param("ss", $username, $password);
		$stmt->execute();
		$qry = $stmt->get_result();
		if($qry->num_rows > 0){
			$res = $qry->fetch_assoc();
			foreach($res as $k => $v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('login_type',1);
			$stmt->close();
			return json_encode(array('status'=>'success'));
		}else{
			$stmt->close();
			return json_encode(array('status'=>'incorrect'));
		}
	}
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_user(){
		extract($_POST);
		$stmt = $this->conn->prepare("SELECT id, firstname, lastname, username, type, avatar FROM `users` WHERE username = ? AND password = md5(?) AND `type` = 2");
		$stmt->bind_param("ss", $username, $password);
		$stmt->execute();
		$qry = $stmt->get_result();
		if($qry->num_rows > 0){
			$res = $qry->fetch_assoc();
			foreach($res as $k => $v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('login_type',2);
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'incorrect';
		}
		$stmt->close();
		return json_encode($resp);
	}
	public function logout_user(){
		if($this->settings->sess_des()){
			redirect('./');
		}
	}
}
// Only run controller actions when this file is accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
	$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
	$auth = new Login();
	switch ($action) {
		case 'login':
			echo $auth->login();
			break;
		case 'login_user':
			echo $auth->login_user();
			break;
		case 'logout':
			echo $auth->logout();
			break;
		case 'logout_user':
			echo $auth->logout_user();
			break;
		default:
			echo $auth->index();
			break;
	}
}

