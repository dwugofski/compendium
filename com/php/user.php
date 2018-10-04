<?php

include_once(dirname(__DIR__)."\ERRORS.php");
include_once(dirname(__DIR__)."\mysql.php");

class User {
	/** @var string $passhash The hash of a user's password */
	private $passhash;
	/** @var int $id The User's id in the database */
	public $id;
	/** @var string $username The User's username */
	public $username;
	/** @var string $email The User's email */
	public $email;
	/** @var array $token The token - if any - associated with a user's login, of form ['selector', 'validator'] */
	public $token;

	/**
	 * Log a user in with desired username and password
	 * 
	 * This function will return an array with a User stored in 'user' if login is successful. Otherwise 
	 * this function will return an array with an int stored in 'error' and string stored in 'msg' 
	 * indicating the error which occurred during login.
	 * 
	 * @param string $username The username assoicated with the User to log in
	 * 
	 * @param string $password The un-hashed password of the user to to log in
	 * 
	 * @param bool $remember_me A boolean to determine whether to log the user in
	 * 
	 * @return array As described above
	 */
	static public function login_user($username, $password, $remember_me=FALSE) {
		if (self::check_user($username) == FALSE) {
			ERRORS::log(ERRORS::USER_ERROR, 'User not found: '.$username);
			return ['error' => ERRORS::USER_ERROR, 'msg' => 'User not found: '.$username];
		} else {
			$resp = self::validate_user($username, $password);
			if ($resp != ERRORS::NO_ERROR) {
				ERRORS::log($resp, 'Invalid password entered for '.$username);
				return ['error' => $resp, 'msg' => 'Invalid password entered for '.$username];
			} else {
				$user_data = self::get_user($username);
				if (isset($user_data['error'])){
					return $user_data;
				}
				$user_data = $user_data['user'];
				$user = new User($username, $user_data['password'], $user_data['id'], $user_data['email']);
				if ($remember_me = TRUE) {
					$user->generate_token();
				}
				return ['user' => $user];
			}
		}
	}

	/**
	 * Log a user in using a login token
	 * 
	 * Upon successful login, the function will return an array with a User object in the 'user' field. If an error 
	 * occurs, an array will be returned with an int 'error' representhing the error type and string 'msg' representing 
	 * the error message.
	 * 
	 * @param string $selector The string associated with the token
	 * 
	 * @param string $validator The validator associated with the token
	 * 
	 * @return array An array as described above
	 */
	static public function login_from_token($selector, $validator) {
		$tokens = self::check_login_token($selector);
		if (!empty($tokens)) {
			$token = $tokens[0];
			if (hash_equals($token['valhash'], hash('sda256', $validator))) {
				$expires = new DateTime($token['expires']);
				$now = new DateTime();
				if ($expires < $now) {
					self::delete_login_token($selector);
					return ['error' => ERRORS::USER_ERROR, 'msg' => 'Login token expired'];
				} else {
					$sql = "SELECT * FROM users WHERE id = ?";
					$users = MYSQL::run_query($sql, 'i', [$token['userid']]);
					if (empty($users)) {
						ERRORS::log(ERRORS::USER_ERROR, 'User with id '.$token['userid'].' tied to token not found');
						self::delete_login_token($selector);
						return ['error' => ERRORS::USER_ERROR, 'msg' => 'User with id '.$token['userid'].' tied to token not found'];
					}
					$user_row = $users[0];
					$user = new User($user_row['username'], $user_row['password'], $user_row['id'], $user_row['email']);
					$user->$token = ['selector' => $selector, 'validator' => $validator];
					return ['user' => $user];
				}
			}
			self::delete_login_token($selector);
		}

		ERRORS::log(ERRORS::USER_ERROR, 'Attempt to use invalid login token detected!');
	}

	/**
	 * Create a new user and log it in
	 * 
	 * This function will return an array with a User stored in 'user' if sign-up/-in is successful. Otherwise 
	 * this function will return an array with an int stored in 'error' and string stored in 'msg' 
	 * indicating the error which occurred during login.
	 * 
	 * @param string $username The username assoicated with the User to log in
	 * 
	 * @param string $password The un-hashed password of the user to to log in
	 * 
	 * @param string|null $email The email of the user to be stored in the database
	 * 
	 * @param bool $remember_me A boolean to determine whether to log the user in
	 * 
	 * @return array As described above
	 */
	static public function create_new_user($username, $password, $email, $remember_me) {
		$sql = "SELECT id FROM users WHERE username = ?";
		$existing_users = MYSQL::run_query($sql, 's', [$username]);
		if (count($existing_users) > 0) {
			ERRORS::log(ERRORS::USER_ERROR, "User ".$username." already exists.");
			return ['error' => ERRORS::USER_ERROR, 'msg' => "User ".$username." already exists."];
		}

		$response = self::validate_username($username);
		if ($response != ERRORS::NO_ERROR) {
			ERRORS::log($response, 'Invalid username: '.$username);
			return ['error' => $response, 'msg' => 'Invalid username: '.$username];
		}
		$passhash = self::hash_password($password);
		$response = self::validate_email($email);
		if ($response != ERRORS::NO_ERROR) {
			ERRORS::log($response, 'Invalid email: '.$email);
			return ['error' => $response, 'msg' => 'Invalid email: '.$email];
		}

		$sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
		$id = MYSQL::run_query($sql, 'sss', [$username, $passhash, $email]);
		$user = self::login_user($username, $password, $remember_me);
		if (isset($user['error'])) return $user;
		Permissions::authorize_user($user['user'], 'guest');
		return $user;
	}

	/**
	 * Verifies a user's existence
	 * 
	 * @param string $username The username to check
	 * 
	 * @return bool Whether the user was found in the database
	 */
	static public function check_user($username) {
		$sql = "SELECT username FROM users WHERE username = ?";
		$users = MYSQL::run_query($sql, 's', [$username]);
		if (empty($users)) return FALSE;
		else return TRUE;
	}

	/**
	 * Validates a user's password
	 * 
	 * @param string $username The user whose password should be checked
	 * 
	 * @param string $passhash The hashed of the user's password
	 * 
	 * @return int An error type if an error occurred, else 0
	 */
	static public function validate_user($username, $password) {
		$sql = "SELECT password FROM users WHERE username = ?";
		$hashes = MYSQL::run_query($sql, 's', [$username]);
		if (empty($hashes)) return ERRORS::USER_ERROR;
		else {
			if (password_verify($password, $hashes[0]['password'])) return ERRORS::NO_ERROR;
			else return ERRORS::USER_ERROR;
		}
	}

	/**
	 * Get a user's data
	 * 
	 * @param string $username The name of the user to get data for
	 * 
	 * @return int An error type if an error occurrec, else 0
	 */
	static public function get_user($username) {
		$sql = "SELECT * FROM users WHERE username = ?";
		$users = MYSQL::run_query($sql, 's', [$username]);
		if (empty($users)) return ['error' => ERRORS::USER_ERROR];
		else {
			return ['user' => $users[0]];
		}
	}

	/**
	 * Get all login tokens associated with a selector
	 * 
	 * @param string $selector The token selector number to check
	 * 
	 * @return array Array of all tokens and all their fields recovered from the database
	 */
	static public function check_login_token($selector) {
		$sql = "SELECT * FROM login_tokens WHERE selector = ?";
		$tokens = MYSQL::run_query($sql, 's', [$selector]);
		return $tokens;
	}

	/**
	 * Delete an existing login token from database
	 * 
	 * @param string $selector The token selector number to check
	 * 
	 * @return void
	 */
	static public function delete_login_token($selector) {
		$sql = "DELETE FROM login_tokens WHERE selector = ?";
		MYSQL::run_query($sql, 's', [$selector]);
	}

	/**
	 * Ensure that a username is of valid format
	 * 
	 * @param string $username The username to check
	 * 
	 * @param int An error type if error, else 0
	 */
	static public function validate_username(&$username) {
		if (substr($username, 0, 1) === '@') $username = substr($username, 1);
		if (preg_match('/[^a-zA-Z\d]+/', $username)) return ERRORS::USER_ERROR;
		if (empty($username)) return ERRORS::USER_ERROR;

		return ERRORS::NO_ERROR;
	}

	/**
	 * Ensure the email is of a valid format
	 * 
	 * @param string $email The email to check
	 * 
	 * @return int An error if an error occurred during validation, otherwise 0
	 */
	static public function validate_email($email) {
		if (empty($email)) return ERRORS::NO_ERROR;
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ERRORS::USER_ERROR;
		else return ERRORS::NO_ERROR;
	}

	/**
	 * Perform a cryptographic hash of a password
	 * 
	 * @param string $password The password to be hashed
	 * 
	 * @return string The cryptographic hash of the password
	 */
	static public function hash_password($password) {
		return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
	}

	/**
	 * Constructor for a User object
	 *
	 * @param string $username The username associated with the User object
	 * 
	 * @param string $passhash The cryptographic hash of the user's password, stored for user verification
	 * 
	 * @param int $id The id of the user in the database
	 * 
	 * @param string $email The user's email
	 * 
	 * @return void
	 */
	public function __construct($username, $passhash, $id, $email) {
		$this->username = $username;
		$this->passhash = $passhash;
		$this->id = $id;
		$this->email = $email;
	}

	/**
	 * Clean up function to log user out
	 * 
	 * @return void
	 */
	public function log_out() {
		if (isset($this->token)) {
			self::delete_login_token($this->token['selector']);
		}
	}

	/**
	 * Generate a token to log a user in
	 * 
	 * Each token is an array with two string elements: 'selector' and 'validator.' This function will either 
	 * return such an array, or it will return an array with an int 'error' and string 'msg' indicating 
	 * an error occurred during generation.
	 * 
	 * @return array as previously described.
	 */
	public function generate_token() {
		$validator = bin2hex(openssl_random_pseudo_bytes(10));
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$found = FALSE;
		for ($i=0; $i<10; $i+=1) {
			if (empty(self::check_login_token($selector))){
				$found = TRUE;
				break;
			} else $selector = random_bytes(10);
		}
		if ($found) {
			$valhash = bin2hex(hash("sha256", $validator));
			$expires = new DateTime();
			$expires->add(new DateInterval('P30D'));

			$sql = "SELECT id FROM users WHERE username = ?";
			$ids = MYSQL::run_query($sql, 's', [$this->username]);
			if (empty($ids)) {
				ERRORS::log(ERRORS::USER_ERROR, 'User not found when generating login token: '.$username);
				return ERRORS::USER_ERROR;
			}

			$sql = "INSERT INTO login_tokens (selector, valhash, userid, expires) VALUES (?, ?, ?, ?)";
			MYSQL::run_query($sql, 'ssis', [$selector, $valhash, $ids[0]['id'], $expires->format('Y-m-d H:i:s')]);

			$this->token = ['selector' => $selector, 'validator' => $validator];
			return ERRORS::NO_ERROR;
		} else {
			ERRORS::log(ERRORS::USER_ERROR, 'Unused login token not found after finite tries');
			return ERRORS::USER_ERROR;
		}
	}
}

?>