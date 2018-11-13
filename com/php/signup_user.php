<?php

include_once(__DIR__."/session.php");
include_once(__DIR__."/errors.php");
include_once(__DIR__."/user.php");
include_once(__DIR__."/json/json_head.php");

// TODO: Limit login attempts -- gate login with capcha after certain # attempts
// Will need to create login table for login attempts from ip address
// Probably will want three levels: immediate access, delayed access, capcha verification

try {

	$username = $_POST['username'];
	$email = $_POST['email'];
	$password = $_POST['pasword'];

	if (!User::validate_username($username)) throw new CompendiumError("Username invalid", TRUE, ERRORS::USER_ERROR);
	if (!User::validate_email($email)) throw new CompendiumError("Email invalid", TRUE, ERRORS::USER_ERROR);
	if (User::check_user($username)) throw new CompendiumError("Username already taken", TRUE, ERRORS::USER_ERROR);
	if (User::check_user_email($email)) throw new CompendiumError("Email already taken", TRUE, ERRORS::USER_ERROR);

	$user = User::create_new_user($username, $password, $email);

	$_SESSION['user'] = $user;
	json_ret($user->data);
} catch (CompendiumError $e) {
	ERRORS::json_log($e);
}



?>