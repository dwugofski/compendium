<?php

include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/user.php");

if (isset($_SESSION['user'])) {
	User::delete_login_token($_SESSION['user']->token['selector']);
	unset($_SESSION['user']);
}

?>