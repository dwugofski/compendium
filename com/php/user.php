<?php

class UserError extends Exception {
	public function __construct($message="", $code=0, $previous = NULL) {
		parent::__construct("UserError: " . $message, $code, $previous);
	}
}

class User {
	public var $username;
	public var $password;

	protected static function verify($username, $password) {
		// Ensure login credentials
	}

	public function __construct($username, $password) {
		try {
			User::verify($username, $password);

		} catch (UserError $ue) {

		}
	}

}

?>