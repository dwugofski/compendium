<?php

class MySQLError extends Exception {
	public function __construct($message="", $code=0, $previous = NULL) {
		parent::__construct("MySQLError: " . $message, $code, $previous);
	}
}

class MySQLConn {
	private var $conn;
	private var $db;
	private var $address;
	private var $username;
	private var $password;

	public function __construct($db, $address, $username, $password) {
		$this->conn = new mysqli($address, $username, $password, $db);
		if ($this->conn->connect_errno != 0){
			throw(MySQLError("Failed to connect to MySQL: " . $this->conn->connect_error));
		}
	}
}

?>