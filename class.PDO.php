<?php

class PDO {

	public $pdo;
	private $error;


	function __construct() {
		$this->connect();
	}

	function add_table_prefix($string){
		return DATABASE_PREFIX . $string;
	}


	function prep_query($query){
		return $this->pdo->prepare($query);
	}


	function connect(){
		if(!$this->pdo){

			$dsn      = 'mysql:dbname=' . DATABASE_NAME . ';host=' . DATABASE_HOST;
			$user     = DATABASE_USER;
			$password = DATABASE_PASS;

			try {
				$this->pdo = new PDO($dsn, $user, $password);
				return true;
			} catch (PDOException $e) {
				$this->error = $e->getMessage();
				die($this->error);
				return false;
			}
		}else{
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			return true;
		}
	}


	function table_exists($table_name){
		$stmt = $this->prep_query('SHOW TABLES LIKE ?');
		$stmt->execute(array($this->add_table_prefix($table_name)));
		return $stmt->rowCount() > 0;
	}


	function execute($query, $values = array()){
		$stmt = $this->pdo->prepare($query);
		$stmt->execute($values);
		return $stmt;
	}

	function fetch($query, $values = array()){
		$stmt = $this->execute($query, $values);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function fetchAll($query, $values = array()){
		$stmt = $this->execute($query, $values);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	function lastInsertId(){
		return $this->pdo->lastInsertId();
	}

}