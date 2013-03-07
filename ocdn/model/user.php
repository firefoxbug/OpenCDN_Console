<?php
/*用户模型*/
class user extends model{
	public function creat($mail,$pass){
		$salt = random('str', 25);
		$passwd = $this->passEncode($pass, $salt);
		$insertArray = array('mail' => $mail, 'salt' => $salt, 'passwd' => $passwd);
		$result = $this->db()->insert('user', $insertArray);
		if($result === false) return false;		//可能会有问题
		return $this->db()->insertId();
	}

	public function find($mail){		//return user_id
		$sql = "SELECT mail,user_id,mail_verify,passwd,salt FROM user WHERE mail = '{$mail}'";
		$result = $this->db()->query($sql, 'row');
		return $result;
	}

	public function get($user_id){
		$sql = "SELECT * FROM user WHERE user_id = '{$user_id}'";
		$result = $this->db()->query($sql, 'row');
		return $result;
	}

	public function sessionCheck($callback = false){
		if(empty($_SESSION['user_id'])){
			if($callback) return $callback();
			else exit(header('Location: ./?default'));
		}return $_SESSION['user_id'];
	}

	public function passEncode($pass, $salt){
		return md5($salt.'?'.$salt.'='.$pass);
	}

	public function regAccess(){
		$result = $this->db()->query('SELECT value FROM config WHERE name = \'reg\'', 'row');
		if($result['value'] == 'true') return true;
		return false;
	}

	public function update($updateArray, $where){
		return $this->db()->update('user', $updateArray, $where);
	}

	public function mailChange($user_id, $mail){
		$token = md5(random('str', 25));

		$base = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		$base = preg_replace('/\?.+$/', "?mail={$token}", $base);
		$content = urlencode("<a href=\"$base\">$base</a>");

		$url = "http://opencdn.sinaapp.com/mail.php?mail={$mail}&title=修改openCDN登陆邮箱";
		$url .= "&content={$content}";
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$content = curl_exec($ch);
		curl_close($ch);

		return $this->update(
			array('change_mail' => $mail, 'change_token' => $token),
			"user_id = '{$user_id}'"
		);
	}

	public function mailChangeToken($token){
		$sql = "SELECT user_id,change_mail FROM user WHERE change_token = '{$token}'";
		$result = $this->db()->query($sql, 'row');
		if(empty($result)) return false;
		$find = $this->find($result['change_mail']);
		if(!empty($find)) return false;
		return $this->update(array('mail' => $result['change_mail'], 'change_mail' => '', 'change_token' => ''),
		 "user_id = '{$result['user_id']}'");
	}

	public function passwdUpdate($user_id, $pass){
		$salt = random('str', 25);
		$passwd = $this->passEncode($pass, $salt);
		$updateArray = array('salt' => $salt, 'passwd' => $passwd);
		return $this->update($updateArray, "user_id = '{$user_id}'");
	}

}


?>