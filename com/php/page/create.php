<?php

include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../user/user.php");
include_once(__DIR__."/page.php");
include_once(__DIR__."/../util/json_head.php");

try {
	$usersel = $_POST["usersel"];
	error_log($_POST);
	$title = $_POST["title"];
	$text = $_POST["text"];

	if (!User::check_user_sel($usersel)) throw new CompendiumError("Invalid user selector", TRUE, ERRORS::USER_ERROR);
	$user = User::get_user_from_sel($usersel);

	if ($user->has_permission('epo') == FALSE) throw new CompendiumError("User does not have permission to create pages", TRUE, ERRORS::USER_ERROR);
	if (Page::validate_title($title) == FALSE) throw new CompendiumError("Title is not valid", TRUE, ERRORS::USER_ERROR);
	if (Page::validate_text($text)   == FALSE) throw new CompendiumError("Text is not valid", TRUE, ERRORS::USER_ERROR);

	$page = Page::create_new_page($user, $title, $text);

	json_ret($page->selector);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	//http_response_code(403);
}

?>