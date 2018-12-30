<?php

include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/user.php");
include_once(__DIR__."/../util/json_head.php");

// TODO: Limit login attempts -- gate login with capcha after certain # attempts
// Will need to create login table for login attempts from ip address
// Probably will want three levels: immediate access, delayed access, capcha verification

try {

	$userident = $_POST['userident'];
	$password = $_POST['password'];
	$token_val = null;
	$token_sel = null;
	$user = null;

	if (isset($_POST['token_val']) && isset($_POST['token_sel'])) {
		$token_val = $_POST['token_val'];
		$token_sel = $_POST['token_sel'];
		if (User::validate_token($token_sel, $token_val)) $user = User::login_from_token($token_sel, $token_val);
		else throw new CompendiumError("Invalid user login token", true, ERRORS::USER_ERROR);
	}
	
	if ($user === NULL) {
		if (User::validate_username($userident)) {
			if (User::is_user($userident, 'username')) {
				$user = new User($userident, 'username');
			} else throw new CompendiumError("User not found", true, ERRORS::USER_ERROR);
		}
		elseif (User::validate_email($userident)) {
			if (User::is_user($userident, 'email')) {
				$user = new User($userident, 'email');
			} else throw new CompendiumError("Email not found", true, ERRORS::USER_ERROR);
		}
		else throw new CompendiumError("Invalid username/email", true, ERRORS::USER_ERROR);

		if (User::validate_user($user, $password)) $user = User::login_user($user, $password, true);
		else throw new CompendiumError("Incorrect password", true, ERRORS::USER_ERROR);
	}

	$_SESSION['user'] = $user;
	json_ret($_SESSION['user']->data);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code(500);
}


?>