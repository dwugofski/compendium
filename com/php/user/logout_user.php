<?php

include_once(__DIR__."/user.php");
include_once(__DIR__."/../util/session.php");

if (isset($_SESSION['user'])) {
	User::delete_login_token($_SESSION['user']->token['selector']);
	unset($_SESSION['user']);
}

?>