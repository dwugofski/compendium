<?php

include_once(__DIR__."/../errors/errors.php");

class MYSQL {
	static private $conn;
	static private $prep_stmt;

	static public function init() {
		$mysql_login_config = parse_ini_file('mysql.ini');
		self::$conn = new mysqli($mysql_login_config['address'], $mysql_login_config['username'], $mysql_login_config['password'], $mysql_login_config['database']);
		if (self::$conn->connect_errno != 0){
			ERRORS::log(ERRORS::MYSQL_ERROR, "Failed to connect to MySQL: " . self::$conn->connect_error);
		}
	}

	static public function prepare($sql, $types=NULL, $inputs=NULL) {
		self::$prep_stmt = self::$conn->prepare($sql);
		if (!(self::$prep_stmt)) {
			ERRORS::log(ERRORS::MYSQL_ERROR, "Prepared statement (" . $sql . ") failed, [" . self::$conn->errno . "]: " . self::$conn->error);
		}

		if (is_array($inputs) && count($inputs) > 0) {
			if (!is_string($types) || strlen($types) != count($inputs)) {
				ERRORS::log(ERRORS::MYSQL_ERROR, "Type string does not fit inputs");
			}
			$tmp_in = array();
			$tmp_in[] = & $types;
			foreach($inputs as $key => $value) $tmp_in[] = & $inputs[$key];
			call_user_func_array(array(self::$prep_stmt, 'bind_param'), $tmp_in);
			if (self::$prep_stmt->errno != 0) ERRORS::log(ERRORS::MYSQL_ERROR, "Error binding inputs for prepared statement (" . $sql . "), [" . self::$prep_stmt->errno . "]: " . self::$prep_stmt->error);
		}		
	}

	static public function execute() {
		if (self::$prep_stmt) {
			if (!self::$prep_stmt->execute()) ERRORS::log(ERRORS::MYSQL_ERROR, "Error executing prepared statement, [" . self::$prep_stmt->errno . "]: " . self::$prep_stmt->error);
			$res = self::$prep_stmt->get_result();
			if ($res) {
				$ret = array();
				while($row = $res->fetch_assoc()){
					$ret[] = $row;
				}
				return $ret;
			}
			else return NULL;
		}
		else ERRORS::log(ERRORS::MYSQL_ERROR, "Attempted to execute with no statement prepared");
	}

	static public function run_query($sql, $types=NULL, $inputs=NULL) {
		self::prepare($sql, $types, $inputs);
		return self::execute();
	}

	static public function get_index() {
		return self::$conn->insert_id;
	}
}

MYSQL::init();

?>