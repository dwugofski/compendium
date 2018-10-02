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
	private var $prep_stmt;

	public function __construct($db, $address, $username, $password) {
		$this->conn = new mysqli($address, $username, $password, $db);
		if ($this->conn->connect_errno != 0){
			throw(MySQLError("Failed to connect to MySQL: " . $this->conn->connect_error));
		}
	}

	public function prepare($sql, &$inputs=NULL) {
		$this->prep_stmt = $conn->perpare($sql);
		if (!($this->prep_stmt)) {
			throw(MySQLError("Prepared statement (" . $sql . ") failed, [" . $this->conn->errno . "]: " . $this->conn->error));
		}

		if (is_array($inputs) && count($inputs) > 0) {
			$tmp_in = array();
			foreach($inputs as $key => $value) $tmp_in[$key] = &($inputs[$key]);
			call_user_func_array(array($this->prep_stmt, 'bind_param'), $tmp_in);
			if ($this->prep_stmt->errno != 0) throw(MySQLError("Error binding inputs for prepared statement (" . $sql . "), [" . $this->prep_stmt->errno . "]: " . $this->prep_stmt->error));
		}		
	}

	public function execute() {
		if ($this->prep_stmt) {
			if (!$this->prep_stmt->execute()) throw(MySQLError("Error executing prepared statement, [" . $this->prep_stmt->errno . "]: " . $this->prep_stmt->error));
			$res = $this->prep_stmt->get_result();
			$ret = array();
			while($row = $res->fetch_assoc()){
				$ret[] = $row;
			}
			return $ret;
		}
		else throw(MySQLError("Attempted to execute with no statement prepared"));
	}

	public function run_query($sql, &$inputs=NULL) {
		$this->prepare($sql, $inputs);
		return $this->execute();
	}
}

?>