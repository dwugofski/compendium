<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../util/mysql.php");
include_once(__DIR__."/user.php");

$selector = $_GET['sel'];

try {
	$tokens = MYSQL::run_query("SELECT userid FROM email_verification_tokens WHERE selector=?", 's', [&$selector]);

	if (empty($tokens)) throw new CompendiumError("Invalid email verification token", TRUE, ERRORS::USER_ERROR);

	$id = $tokens[0]['userid'];
	if (!User::check_userid($id)) throw new CompendiumError("User not found for email verification token", TRUE, ERRORS::USER_ERROR);

	$user = get_user_from_id($id);
	$perm_level = User::perm_level_to_title($user->get_perm_level());
	if ($perm_level == User::PERM_GUEST) {
		$user->grant_permissions(User::PERM_USER);
	}

} catch(CompendiumError $e) {
	echo($e->getMessage());
}

?>