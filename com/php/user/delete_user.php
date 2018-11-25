<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/user.php");
include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/../util/json_head.php");

$password = $_POST['password'];

$user = $_SESSION['user'];

try {
	if (!isset($user)) throw new CompendiumError("No user logged in. Cannot delete.");
	if (!User::validate_user($user->username, $password)) throw new CompendiumError("Incorrect password");

	unset($_SESSION['user']);
	User::delete_userid($user->id);
	json_ret(["msg" => "User successfully deleted"]);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code(403);
}

?>