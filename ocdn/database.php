<?php

class database {
	private $dbHost = 'localhost';
	private $dbName = 'cdn_info';
	private $dbUser = 'root';
	private $dbPass = 'DP';
	private $dbObj = false;

	function __construct(){
		//$this->dbObj = new PDO("mysql:host={$this->dbHost};dbname={$this->dbName};charset=UTF-8", $this->dbUser, $this->dbPass);
		$this->dbObj = new PDO("mysql:host={$this->dbHost};dbname={$this->dbName};charset=UTF-8", $this->dbUser, $this->dbPass, Array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES'UTF8';"));
		$this->dbObj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function query($sql, $type = 'array'){
		//$sql = $this->dbObj->quote($sql);
		switch($type){
			case 'array':
				$dbObj = $this->dbObj->query($sql);
				if(!$dbObj) return false;
				$result = $dbObj->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'row':
				$dbObj = $this->dbObj->query($sql);
				if(!$dbObj) return false;
				$result = $dbObj->fetch(PDO::FETCH_ASSOC);
				break;
			case 'exec':
				$result = $this->dbObj->exec($sql);
				break;
		}
		if($result) return $result;
		else return Array();
	}

	public function insert($table, $insertArray){   //单引号问题
		if(empty($insertArray)) return false;
		$columns = array_keys($insertArray);
		$values = array_values($insertArray);
		unset($insertArray);
		foreach($values as $key => $value){
			if(is_bool($value)){
				$value = $value ? 'true' : 'false';
			}
			$values[$key] = $this->dbObj->quote($value);
		}
		foreach($columns as $key => $value){
			$columns[$key] = $table.'.'.$value;
		}
		$columns = implode(',', $columns);
		$values = implode(',', $values);
		$query = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
		return $this->dbObj->exec($query);
	}

	public function update($table, $updateArray, $where){
		if(empty($updateArray) || empty($where)) return false;
		$updates = Array();
		foreach ($updateArray as $key => $value){
			if(is_bool($value)){
				$value = $value ? 'true' : 'false';
			}
			if($value != NULL){
				$updates[] = $key.'='.$this->dbObj->quote($value);
			}else{
				$updates[] = $key.'= NULL';
			}
		}
		unset($updateArray);
		$updates = implode(',', $updates);
		$query = "UPDATE {$table} SET {$updates} WHERE {$where}";
		return $this->dbObj->exec($query);
	}

	public function del($table, $where){
		$query = "DELETE FROM $table WHERE {$where}";
		//echo $query;
		return $this->dbObj->exec($query);
	}

	public function insertId(){
		return $this->dbObj->lastInsertId(); 
	}

	public function beginTransaction(){
		return $this->dbObj->beginTransaction();
	}

	public function commit(){
		return $this->dbObj->commit();
	}

	public function rollBack(){
		return $this->dbObj->rollBack();
	}

}

?>
