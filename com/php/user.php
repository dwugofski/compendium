<?php

include_once(dirname(__DIR__)."\errors.php");
include_once(dirname(__DIR__)."\mysql.php");

class User implements Hashable {
	const PERM_ROOT 	= 'root';
	const PERM_ADMIN 	= 'admin';
	const PERM_USER 	= 'user';
	const PERM_GUEST 	= 'guest';

	// root actions
	const ACT_EDIT_ALL_ADMINS 		= 'eaa';
	// admin actions
	const ACT_EDIT_ALL_USERS 		= 'eua';
	const ACT_EDIT_ALL_THEMES 		= 'eta';
	const ACT_EDIT_ALL_PAGES 		= 'epa';
	const ACT_LOCK_ALL_PAGES 		= 'lpa';
	const ACT_OPEN_ALL_PAGES 		= 'gpa';
	const ACT_EDIT_ALL_COMMENTS 	= 'eca';
	const ACT_ADD_ALL_COMMENTS 		= 'aca';
	const ACT_VIEW_ALL_PAGES 		= 'vpa';
	// user actions
	const ACT_EDIT_OWN_USER 		= 'euo';
	const ACT_EDIT_OWN_THEMES 		= 'eto';
	const ACT_EDIT_OWN_PAGES 		= 'epo';
	const ACT_LOCK_OWN_PAGES 		= 'lpo';
	const ACT_OPEN_OWN_PAGES 		= 'gpo';
	const ACT_EDIT_OWN_COMMENTS 	= 'eco';
	const ACT_ADD_OWN_COMMENTS 		= 'aco';
	const ACT_VIEW_OWN_PAGES 		= 'vpo';
	const ACT_EDIT_OPEN_PAGES 		= 'epg';
	const ACT_ADD_OPEN_COMMENTS 	= 'acg';
	// user actions
	const ACT_VIEW_UNLOCKED_PAGES 	= 'vpu';


	/** @var int $id The User's id in the database */
	public $id;
	/** @var array $token The token - if any - associated with a user's login, of form ['selector', 'validator'] */
	public $token;

	static public function login_user($username, $password, $remember_me=FALSE) {
		if (self::check_user($username) == FALSE) {
			ERRORS::log(ERRORS::USER_ERROR, sprintf("User '%s' not found", $username));
		} else {
			if (!self::validate_user($username, $password)) {
				ERRORS::log($resp, sprintf("Invalid password entered", $password));
			} else {
				$user_data = self::get_user($username);
				$user = new User($user_data['id']);
				if ($remember_me = TRUE) {
					$user->generate_token();
				}
				return $user;
			}
		}
	}

	static public function login_from_token($selector, $validator) {
		$tokens = self::check_login_token($selector);
		if (!empty($tokens)) {
			$token = $tokens[0];
			if (hash_equals($token['valhash'], hash('sda256', $validator))) {
				$expires = new DateTime($token['expires']);
				$now = new DateTime();
				if ($expires < $now) {
					self::delete_login_token($selector);
					ERRORS::log(ERRORS::USER_ERROR, "Login token expired");
				} else {
					$sql = "SELECT id FROM users WHERE id = ?";
					$users = MYSQL::run_query($sql, 'i', [&$token['userid']]);
					if (empty($users)) {
						ERRORS::log(ERRORS::USER_ERROR, "Login token's user not found");
						self::delete_login_token($selector);
					}
					$user = new User($token['userid']);
					$user->$token = ['selector' => $selector, 'validator' => $validator];
					return $user;
				}
			}
			self::delete_login_token($selector);
		}

		ERRORS::log(ERRORS::USER_ERROR, 'Attempt to use invalid login token detected!');
	}

	static public function create_new_user($username, $password, $email=NULL, $remember_me=FALSE) {
		$sql = "SELECT id FROM users WHERE username = ?";
		$existing_users = MYSQL::run_query($sql, 's', [$username]);
		if (!is_empty($existing_users)) {
			ERRORS::log(ERRORS::USER_ERROR, sprintf("User '%s' already exists", $username));
		}

		if (!self::validate_username($username)) {
			ERRORS::log(ERRORS::USER_ERROR, "Invalid username: %s", $username);
		}
		$passhash = self::hash_password($password);
		if (!self::validate_email($email)) {
			ERRORS::log(ERRORS::USER_ERROR, "Invalid email: %s", $email);
		}

		$sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
		MYSQL::run_query($sql, 'sss', [$username, $passhash, $email]);
		$id = MYSQL::get_index();
		$user = self::login_user($username, $password, $remember_me);
		$user->grant_permissions(User::PERM_GUEST);
		return $user;
	}

	static public function check_user($username) {
		$sql = "SELECT username FROM users WHERE username = ?";
		$users = MYSQL::run_query($sql, 's', [$username]);
		if (empty($users)) return FALSE;
		else return TRUE;
	}

	static public function validate_user($username, $password) {
		$sql = "SELECT password FROM users WHERE username = ?";
		$hashes = MYSQL::run_query($sql, 's', [$username]);
		if (empty($hashes)) return ERRORS::USER_ERROR;
		else {
			if (password_verify($password, $hashes[0]['password'])) return TRUE;
			else return FALSE;
		}
	}

	static public function get_user($username) {
		$sql = "SELECT * FROM users WHERE username = ?";
		$users = MYSQL::run_query($sql, 's', [$username]);
		if (empty($users)) return ['error' => ERRORS::USER_ERROR];
		else {
			return ['user' => $users[0]];
		}
	}

	static public function get_user_from_id($userid) {
		$sql = "SELECT * FROM users WHERE id = ?";
		$users = MYSQL::run_query($sql, 's', [$userid]);
		if (empty($users)) return ['error' => ERRORS::USER_ERROR];
		else {
			return ['user' => $users[0]];
		}
	}

	static public function check_login_token($selector) {
		$sql = "SELECT * FROM login_tokens WHERE selector = ?";
		$tokens = MYSQL::run_query($sql, 's', [$selector]);
		return $tokens;
	}

	static public function delete_login_token($selector) {
		$sql = "DELETE FROM login_tokens WHERE selector = ?";
		MYSQL::run_query($sql, 's', [$selector]);
	}

	static public function validate_username(&$username) {
		if (substr($username, 0, 1) === '@') $username = substr($username, 1);
		if (preg_match('/[^a-zA-Z\d]+/', $username)) return FALSE;
		if (empty($username)) return FALSE;

		return TRUE;
	}

	static public function validate_email($email) {
		if (empty($email)) return ERRORS::NO_ERROR;
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return FALSE;
		else return TRUE;
	}

	static public function hash_password($password) {
		return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
	}

	public function __construct($id) {
		// Verify user exists
		$this->id = $id;
	}

	public function log_out() {
		if (isset($this->token)) {
			self::delete_login_token($this->token['selector']);
		}
	}

	public function generate_token() {
		$validator = bin2hex(openssl_random_pseudo_bytes(10));
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$found = FALSE;
		for ($i=0; $i<10; $i+=1) {
			if (empty(self::check_login_token($selector))){
				$found = TRUE;
				break;
			} else $selector = bin2hex(openssl_random_pseudo_bytes(12));
		}
		if ($found) {
			$valhash = bin2hex(hash("sha256", $validator));
			$expires = new DateTime();
			$expires->add(new DateInterval('P30D'));

			$sql = "SELECT id FROM users WHERE id = ?";
			$ids = MYSQL::run_query($sql, 'i', [$this->id]);
			if (empty($ids)) {
				ERRORS::log(ERRORS::USER_ERROR, 'User not found when generating login token: '.$this->id);
				return FALSE;
			}

			$sql = "INSERT INTO login_tokens (selector, valhash, userid, expires) VALUES (?, ?, ?, ?)";
			MYSQL::run_query($sql, 'ssis', [$selector, $valhash, $this->id, $expires->format('Y-m-d H:i:s')]);

			$this->token = ['selector' => $selector, 'validator' => $validator];
			return TRUE;
		} else {
			ERRORS::log(ERRORS::USER_ERROR, 'Unused login token not found after finite tries');
			return FALSE;
		}
	}

	public function has_permission($action) {
		$permission_level = (MYSQL::run_query("SELECT permission_level FROM user_roles WHERE user_id = ?", 'i', [$this->id]))[0]['permission_level'];
		if (MYSQL::run_query("SELECT id FROM permission_actions WHERE description = ? AND permission_level = ?", 'si', [$action, $permission_level])) return TRUE;
		else return FALSE;
	}

	public function grant_permissions($permission_level) {
		$ids = MYSQL::run_query("SELECT id FROM permissions WHERE title = ?", 's', $permission_level);
		if(is_empty($ids)) ERRORS::log(ERRORS::PERMISSIONS_ERROR, sprintf("Permission level '%s' not found", $permission_level));
		MYSQL::run_query("DELETE FROM user_roles WHERE id = ?", 'i', &$this->id);
		MYSQL::run_query("INSERT INTO user_roles (permission_level, user_id) VALUES (?, ?)", 'ii', [&$ids[0]['id'], &$this->id]);
	}

	// Hashable functions

	public function equals($other_user) {
		return ($this->id == $other_user->id);
	}

	public function hash() {
		return hash('ripemd160', $this->id);
	}
}

?>