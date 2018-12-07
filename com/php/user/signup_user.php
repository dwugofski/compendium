<?php

include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/../util/mysql.php");
include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/user.php");
include_once(__DIR__."/../util/json_head.php");

// TODO: Limit login attempts -- gate login with capcha after certain # attempts
// Will need to create login table for login attempts from ip address
// Probably will want three levels: immediate access, delayed access, capcha verification

function generate_email_vefication_token($userid) {
	$selector = bin2hex(openssl_random_pseudo_bytes(12));
	$found = TRUE;
	$count = 0;
	MYSQL::prepare("SELECT id FROM email_verification_tokens WHERE selector = ?", 's', [&$selector]);
	while ($found) {
		$entries = MYSQL::execute();
		if (empty($entries)) $found = FALSE;
		else $selector = bin2hex(openssl_random_pseudo_bytes(12));

		if ($count > 100) break;
		$count += 1;
	}
	if (!$found) {
		MYSQL::run_query("INSERT INTO email_verification_tokens (selector, userid) VALUES (?, ?)", 'si', [$selector, $userid]);
		return $selector;
	} else ERRORS::log(ERRORS::USER_ERROR, "Failed to find an unclaimed selector for email verification in 100 tries");

	return NULL;
}

try {

	$username = $_POST['username'];
	$email = $_POST['email'];
	$password = $_POST['password'];

	if (!User::validate_username($username)) throw new CompendiumError("Username invalid", TRUE, ERRORS::USER_ERROR);
	if (!User::validate_email($email)) throw new CompendiumError("Email invalid", TRUE, ERRORS::USER_ERROR);
	if (User::is_user($username, 'username')) throw new CompendiumError("Username already taken", TRUE, ERRORS::USER_ERROR);
	if (User::is_user($email, 'email')) throw new CompendiumError("Email already taken", TRUE, ERRORS::USER_ERROR);

	$user = User::create_new_user($username, $password, $email);
	$user->grant_permissions(User::PERM_USER); // Going to want to verify email later on

	$_SESSION['user'] = $user;
	json_ret($user->data);
} catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code(403);
}



?>