<?php

namespace A1phanumeric;

use \PDO;
use \PDOException;

class DBPDO
{

	private static $instance = null;
	public $pdo;
	private $error;
	private $dbname;
	private $dbhost;
	private $dbuser;
	private $dbpass;
	private $orderwise;

	public static function getInstance($dbhost, $dbname, $dbuser, $dbpass, $orderwise = false)
	{
		if (self::$instance === null) {
			self::$instance = new self($dbhost, $dbname, $dbuser, $dbpass, $orderwise);
		}
		return self::$instance;
	}

	function __construct($dbhost = '', $dbname = '', $dbuser = '', $dbpass = '', $orderwise = false)
	{
		$this->dbhost = $dbhost;
		$this->dbname = $dbname;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->orderwise = $orderwise;
		$this->connect();
	}

	// Disallow cloning and unserializing
    private function __clone() {}
    private function __wakeup() {}


	function prep_query($query)
	{
		return $this->pdo->prepare($query);
	}


	function connect()
	{
		if (!$this->pdo) {
			if($this->orderwise){
				$dsn  = 'sqlsrv:Server=' . $this->dbhost . ';Database=' . $this->dbname . ';Encrypt=no';
			}else{
				$dsn  = 'mysql:dbname=' . $this->dbname . ';host=' . $this->dbhost . ';charset=utf8mb4';
			}
			$user     = $this->dbuser;
			$password = $this->dbpass;

			try {
				if($this->orderwise){
					$this->pdo = new PDO($dsn, $user, $password);
				}else{
					$this->pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_PERSISTENT => true));
				}
				return true;
			} catch (PDOException $e) {
				$this->error = $e->getMessage();
				// die($this->error);
				return false;
			}
		} else {
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			return true;
		}
	}


	function table_exists($table_name)
	{
		$stmt = $this->prep_query('SHOW TABLES LIKE ?');
		$stmt->execute(array($table_name));
		return $stmt->rowCount() > 0;
	}


	function execute($query, $values = null, $debug = false)
	{
		if ($values == null) {
			$values = array();
		} else if (!is_array($values)) {
			$values = array($values);
		}
		$stmt = $this->prep_query($query);
		if($debug){
			echo $query;
			print_r($values);
			die();
		}
		try {
			$stmt->execute($values);
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
			die($query . "<br />\n" . $this->error);
			return false;
		}
		return $stmt;
	}

	function fetch($query, $values = null)
	{
		if ($values == null) {
			$values = array();
		} else if (!is_array($values)) {
			$values = array($values);
		}
		$stmt = $this->execute($query, $values);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function fetchAll($query, $values = null, $key = null)
	{
		if ($values == null) {
			$values = array();
		} else if (!is_array($values)) {
			$values = array($values);
		}
		$stmt = $this->execute($query, $values);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Allows the user to retrieve results using a
		// column from the results as a key for the array
		if(!empty($results)){
			if ($key != null) {
				$keyed_results = array();
				foreach ($results as $result) {
					$keyed_results[$result[$key]] = $result;
				}
				$results = $keyed_results;
			}
		}
		return $results;
	}

	function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}
}
