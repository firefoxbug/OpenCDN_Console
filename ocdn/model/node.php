<?php
/*节点模型*/
class node extends model{
	public function creat($ip){
		$insertArray = array('NodeIP' => $ip);
		$result = $this->db()->insert('node_info', $insertArray);
		$this->db()->update('config', array('value' => 'true'), 'name = \'restart\'');
		if($result === false) return false;		//可能会有问题
		return $this->db()->insertId();
	}

	public function nodeList($start = 0, $limit = 0, $valid = false){
		if($limit == 0 && $start == 0) $limitSql = '';
		else $limitSql = " LIMIT {$start},{$limit} ";
		if($valid) $where = " WHERE node_access = 'allow' AND Status = 'on'";
		else $where = '';
		$sql = "SELECT * FROM node_info {$where} {$limitSql}";
		return $this->db()->query($sql, 'array');
	}


	public function find($ip){
		if(ctype_digit($ip)) $sql = "SELECT * FROM node_info WHERE node_id = '{$ip}'";
		else $sql = "SELECT * FROM node_info WHERE NodeIP = '{$ip}'";
		$result = $this->db()->query($sql, 'row');
		return $result;
	}

	public function remove($id){
		$result = $this->db()->del('node_info', "node_id = '{$id}'");
		$this->db()->update('config', array('value' => 'true'), 'name = \'restart\'');
		return $result;
	}

	public function update($updateArray, $where){
		return $this->db()->update('node_info', $updateArray, $where);
	}

	public function confUpdate($ipArray){
		$dir = '../conf/opencdn.conf';
		//$dir = '/etc/opencdn.conf';
		if(!file_exists($dir)) return false;
		$file = file_get_contents($dir);
		$file = explode('[Node]', $file);
		$file = $file[0];
		$file .= "[Node]\n";
		foreach ($ipArray as $key => $value) {
			$file .= "Node{$key}={$value}\n";
		}
		$source = fopen($dir, 'w');
		fwrite($source, $file);
		fclose($source);
		return true;
	}


}


?>