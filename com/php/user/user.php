<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../util/mysql.php");
include_once(__DIR__."/../util/comp_accessor.php");

class User extends CompAccessor {

// --------------------------------------------------
// Begin static features
// --------------------------------------------------
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

	const TABLE_NAME = 'users';
	const PRIMARY_KEY = 'id';

	const COLUMN_NAMES =[
		'id',
		'username',
		'password',
		'email',
		'selector',
		'created',
		'modified'
	];

	const COLUMN_TYPES = [
		'id' => 'i',
		'username' => 's',
		'password' => 's',
		'email' => 's',
		'selector' => 's',
		'created' => 's',
		'modified' => 's'

	];

	const IDENTIFIERS = [
		'id' => 'id',
		'selector' => 'selector',
		'sel' => 'selector',
		'username' => 'username',
		'user' => 'username',
		'email' => 'email'
	];

	static private function make_selector() {
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$unique = TRUE;
		MYSQL::prepare("SELECT id FROM users WHERE selector = ?", "s", [&$selector]);
		for ($i=0; $i<10; $i+=1) {
			if (!empty(MYSQL::execute())) {
				$unique = TRUE;
			} else {
				$selector = bin2hex(openssl_random_pseudo_bytes(12));
			}
		}
		if ($unique) return $selector;
		else ERRORS::log(ERRORS::PAGE_ERROR("Could not establish a unique selector for users\n"));
	}

	static public function login_user($user, $password, $remember_me=FALSE) {
		if (self::is_user($user->id) == FALSE) {
			ERRORS::log(ERRORS::USER_ERROR, sprintf("User '%s' not found", $username));
		} else {
			if (self::validate_user($user, $password)) {
				if ($remember_me = TRUE) {
					$user->generate_token();
				}
				return $user;
			}
		}

		return null;
	}

	static public function login_from_token($selector, $validator) {
		if (self::validate_token($selector, $validator)) {
			$sql = "SELECT id FROM users WHERE id = ?";
			$users = MYSQL::run_query($sql, 'i', [&$token['userid']]);
			if (empty($users)) {
				ERRORS::log(ERRORS::USER_ERROR, "Login token's user not found");
				self::delete_login_token($selector);
			}
			$user = new User($token['userid']);
			$user->token = ['selector' => $selector, 'validator' => $validator];
			return $user;
		} else ERRORS::log(ERRORS::USER_ERROR, 'Attempt to use invalid login token detected!');
	}

	static public function create_new_user($username, $password, $email=NULL, $remember_me=FALSE) {
		$sql = "SELECT id FROM users WHERE username = ?";
		$existing_users = MYSQL::run_query($sql, 's', [$username]);
		if (!empty($existing_users)) {
			ERRORS::log(ERRORS::USER_ERROR, sprintf("User '%s' already exists", $username));
		}

		if (!self::validate_username($username)) {
			ERRORS::log(ERRORS::USER_ERROR, "Invalid username: %s", $username);
		}
		$passhash = self::hash_password($password);
		if (!self::validate_email($email)) {
			ERRORS::log(ERRORS::USER_ERROR, "Invalid email: %s", $email);
		}

		$selector = self::make_selector();
		$sql = "INSERT INTO users (username, password, email, selector) VALUES (?, ?, ?, ?)";
		MYSQL::run_query($sql, 'ssss', [&$username, &$passhash, &$email, &$selector]);
		$id = MYSQL::get_index();
		$user = new User($username, 'username');
		$user = self::login_user($user, $password, $remember_me);
		$user->grant_permissions(User::PERM_GUEST);
		return $user;
	}

	static public function delete_user($value, $identifier='id') {
		$rows = self::_find($value, $identifier);
		if (empty($rows)) ERRORS::log(ERRORS::USER_ERROR, "User::delete_user() Cannot find user '%s' => '%s'", $identifier, $value);
		else {
			MYSQL::run_query("DELETE FROM users WHERE id = ?", 'i', [$rows[0]['id']]);
		}
	}

	static public function is_user($value, $identifier='id') {
		return self::is($value, $identifier);
	}

	static public function validate_user($user, $password) {
		$sql = "SELECT password FROM users WHERE id = ?";
		$hashes = MYSQL::run_query($sql, 's', [$user->id]);
		if (empty($hashes)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to validate unknown user '%s'", $user->id);
		else {
			if (password_verify($password, $hashes[0]['password'])) return TRUE;
			else return FALSE;
		}
	}

	static public function validate_token($selector, $validator) {
		$tokens = self::check_login_token($selector);
		if (!empty($tokens)) {
			$token = $tokens[0];
			if (hash_equals($token['valhash'], hash('sda256', $validator))) {
				$expires = new DateTime($token['expires']);
				$now = new DateTime();
				if ($expires < $now) {
					self::delete_login_token($selector);
				} else {
					$sql = "SELECT id FROM users WHERE id = ?";
					$users = MYSQL::run_query($sql, 'i', [&$token['userid']]);
					if (empty($users)) {
						self::delete_login_token($selector);
					} else return TRUE;
				}
			}
			self::delete_login_token($selector);
		}

		return FALSE;
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
		if (empty($email)) return FALSE;
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return FALSE;
		else return TRUE;
	}

	static public function email_exists($email) {
		$emails = MYSQL::run_query("SELECT id FROM users WHERE email = ?", 's', [&$email]);
		return !empty($emails);
	}

	static public function hash_password($password) {
		return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
	}

	static public function perm_level_to_title($permission_level) {
		return MYSQL::run_query("SELECT title FROM permissions WHERE id = ?", 'i', [&$permission_level])[0]['title'];
	}
// --------------------------------------------------
// Begin non-static features
// --------------------------------------------------
	private $token;

	public function __construct($value, $identifier='id') {
		$rows = $this->_find($value, $identifier);
		if (!empty($rows)) {
			$this->id = $rows[0]['id'];
		}
		else ERRORS::log(
			ERRORS::PAGE_ERROR, 
			"User::__construct() could not find user '%s' => '%s'", 
			json_encode($identifier), 
			json_encode($value)
		);
	}

	public function __get($name) {
		switch($name){
			case "blockeds":
				return $this->get_followeds();
			case "blockers":
				return $this->get_followers();
			case "id":
				return $this->id;
			case "username":
				return $this->get_username();
			case "token":
				return $this->token;
			case "email":
				return $this->get_email();
			case "followeds":
				return $this->get_followeds();
			case "followers":
				return $this->get_followers();
			case "selector":
				return $this->get_selector();
			case "permissions":
				return $this->get_permissions();
			case "data":
				return ["username" => $this->get_username(), "token" => $this->token, "email" => $this->email, "selector" => $this->get_selector()];
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to get unknown property '%s' of user", $name);
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case "email":
				$this->set_email($value);
				break;
			case "username":
				$this->set_username($value);
				break;
			case "permission":
				return $this->grant_permissions($value);
			case "blockeds":
			case "blockers":
			case "id":
			case "data":
			case "followers":
			case "followeds":
			case "token":
			case "selector":
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to set read-only property '%s' of user", $name);
				break;
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to set unknown property '%s' of user", $name);
		}
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

	public function get_perm_level() {
		return MYSQL::run_query("SELECT permission_level FROM user_roles WHERE user_id = ?", 'i', [&$this->id])[0]['permission_level'];
	}

	public function get_permissions() {
		$perm_level = $this->get_perm_level();
		$actions = MYSQL::run_query("SELECT description FROM permission_actions WHERE permission_level = ?", 'i', [&$perm_level]);
		$permissions = array();
		foreach($actions as $i=>$action){
			$permissions[$i] = $action['description'];
		}
		return $permissions;
	}

	public function has_permission($action) {
		// Edit this if we do first-run initialization... currently is optimized for every-time querying
		$permission_level = (MYSQL::run_query("SELECT permission_level FROM user_roles WHERE user_id = ?", 'i', [&$this->id]))[0]['permission_level'];
		if (MYSQL::run_query("SELECT id FROM permission_actions WHERE description = ? AND permission_level = ?", 'si', [$action, $permission_level])) return TRUE;
		else return FALSE;
	}

	public function grant_permissions($permission_level) {
		$ids = MYSQL::run_query("SELECT id FROM permissions WHERE title = ?", 's', [&$permission_level]);
		if(empty($ids)) ERRORS::log(ERRORS::PERMISSIONS_ERROR, sprintf("Permission level '%s' not found", $permission_level));
		MYSQL::run_query("DELETE FROM user_roles WHERE user_id = ?", 'i', [&$this->id]);
		MYSQL::run_query("INSERT INTO user_roles (permission_level, user_id) VALUES (?, ?)", 'ii', [&$ids[0]['id'], &$this->id]);
	}

	public function get_email(){
		$emails = MYSQL::run_query("SELECT email FROM users WHERE id = ?", 'i', [&$this->id]);
		if (empty($emails)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to get email of unknown user %d", $this->id);
		return $emails[0]['email'];
	}

	public function set_email($email){
		if (User::email_exists($email)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to set email to '%s' despite already being in use", $email);
		if (!User::validate_email($email)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to set email to invalid value '%s'", $email);

		MYSQL::run_query("UPDATE users SET email = ? WHERE id = ?", 'si', [&$email, &$this->id]);
	}

	public function get_selector() {
		$selector = MYSQL::run_query("SELECT selector FROM users WHERE id = ?", 'i', [&$this->id])[0]["selector"];
		return $selector;
	}

	public function get_username() {
		$usernames = MYSQL::run_query("SELECT username FROM users WHERE id = ?", "i", [&$this->id]);
		if (empty($usernames)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to get username of unknown user id '%d'", $this->id);
		return $usernames[0]['username'];
	}

	public function set_username($username) {
		if (User::is_user($username, 'username')) ERRORS::log(ERRORS::USER_ERROR, "Attempted to set username to '%s' despite already being in use", $username);
		if (!User::validate_username($username)) ERRORS::log(ERRORS::USER_ERROR, "Attempted to set username to invalid value '%s'", $username);

		MYSQL::run_query("UPDATE users SET username = ? WHERE id = ?", 'si', [&$username, &$this->id]);
	}

	public function get_followers() {
		$rows = MYSQL::run_query("SELECT follower FROM followings WHERE followed = ?", 'i', $this->id);
		$followers = array();
		foreach ($rows as $follower) {
			$followers[] = new User($follower['follower']);
		}
		return $followers;
	}

	public function get_followeds() {
		$rows = MYSQL::run_query("SELECT followed FROM followings WHERE follower = ?", 'i', $this->id);
		$followeds = array();
		foreach ($rows as $followed) {
			$followeds[] = new User($followed['followed']);
		}
		return $followeds;
	}

	public function is_follower($user) {
		foreach ($this->followers as $follower) {
			if (self::equals($user, $follower)) return true;
		}
		return false;
	}

	public function is_followed($user) {
		foreach ($this->followeds as $followed) {
			if (self::equals($user, $followed)) return true;
		}
		return false;
	}

	public function follow($user) {
		if (!is_followed($user) && !is_blocker($user)) {
			MYSQL::run_query("INSERT INTO followings (follower, followed) VALUES (?, ?)", 'ii', [$this->id, $user->id]);
		} else ERRORS::log(
			ERRORS::USER_ERROR,
			"User::follow() User '%s' cannot follower user '%s' as they either already do, or are blocked",
			$this->username,
			$user->username
		);
	}

	public function unfollow($user) {
		if ($this->is_followed($user)) MYSQL::run_query("DELETE FROM followings WHERE follower = ? AND followed = ?", 'ii', [$this->id, $user->id]);
	}

	public function get_blockers() {
		$rows = MYSQL::run_query("SELECT blocker FROM user_blocks WHERE blocked = ?", 'i', [$this->id]);
		$blockers = array();
		foreach ($blockers as $blocker) {
			$blockers[] = new User($blocker['blocker']);
		}
		return $blockers;
	}

	public function get_blockeds() {
		$rows = MYSQL::run_query("SELECT blocked FROM user_blocks WHERE blocker = ?", 'i', [$this->id]);
		$blockeds = array();
		foreach ($blockeds as $blocked) {
			$blockeds[] = new User($blocked['blocked']);
		}
		return $blockeds;
	}

	public function is_blocker($user) {
		foreach ($this->blockers as $blocker) {
			if (self::equals($user, $blocker)) return true;
		}
		return false;
	}

	public function is_blocked($user) {
		foreach ($this->blockeds as $blocked) {
			if (self::equals($user, $blocked)) return true;
		}
		return false;
	}

	public function block($user) {
		if (!$this->is_blocked($user)) {
			if ($this->is_followed($user)) $this->unfollow($user);
			MYSQL::run_query("INSERT INTO user_blocks (blocker, blocked) VALUES (?, ?)", 'ii', [$this->id, $user->id]);
		} else ERRORS::log(
			ERRORS::USER_ERROR,
			"User::follow() User '%s' cannot block user '%s' as they do",
			$this->username,
			$user->username
		);
	}

	public function unblock($user) {
		if ($this->is_blocked($user)) MYSQL::run_query("DELETE FROM user_blocks WHERE blocker = ? AND blocked = ?", 'ii', [$this->id, $user->id]);
	}
}

?>