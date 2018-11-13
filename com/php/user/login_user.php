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
	$token_val = $_POST['token_val'];
	$token_sel = $_POST['token_sel'];
	$user = NULL;
	$username = NULL;

	if ($token_val !== NULL && $token_sel !== NULL) {
		if (User::validate_token($token_sel, $token_val)) $user = User::login_from_token($token_sel, $token_val);
		else throw new CompendiumError("Invalid user login token", TRUE, ERRORS::USER_ERROR);
	}
	
	if ($user === NULL) {
		if (User::validate_username($userident)) {
			if (User::check_user($userident)) {
				$username = $userident;
			} else throw new CompendiumError("User not found", TRUE, ERRORS::USER_ERROR);
		}
		elseif (User::validate_email($userident)) {
			if (User::check_user_email($userident)) {
				$username = User::get_user_from_email($userident)->username;
			} else throw new CompendiumError("Email not found", TRUE, ERRORS::USER_ERROR);
		}
		else throw new CompendiumError("Invalid username/email", TRUE, ERRORS::USER_ERROR);

		if (User::validate_user($username, $password)) {
			$user = User::login_user($username, $password, TRUE);
		} else throw new CompendiumError("Incorrect password", TRUE, ERRORS::USER_ERROR);
	}

	$_SESSION['user'] = $user;
	json_ret($_SESSION['user']->data);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code(403);
}


?>