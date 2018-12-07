<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../user/user.php");
include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/page.php");
include_once(__DIR__."/../util/json_head.php");

try {
	$title = $_POST["title"];
	$description = $_POST["description"];
	$text = $_POST["text"];
	$user = null;
	$parent = null;

	if (!isset($_SESSION['user'])) throw new CompendiumError("Must be logged in to create pages", true, ERRORS::USER_ERROR, 401);
	$user = $_SESSION['user'];
	if (!User::check_userid($user->id)) throw new CompendiumError("User not recognized", true, ERRORS::USER_ERROR, 401);

	if ($user->has_permission('epo') == FALSE) throw new CompendiumError("User does not have permission to create pages", TRUE, ERRORS::USER_ERROR, 401);
	if (Page::validate_title($title) == FALSE) throw new CompendiumError("Title is not valid", TRUE, ERRORS::PAGE_ERROR, 400);
	if (Page::validate_description($description) == FALSE) throw new CompendiumError("Subtitle is not valid", TRUE, ERRORS::PAGE_ERROR, 400);
	if (Page::validate_text($text)   == FALSE) throw new CompendiumError("Text is not valid", TRUE, ERRORS::PAGE_ERROR, 400);
	if (isset($_POST['parent']) && $_POST['parent'] != "") {
		if (!Page::is_page($_POST['parent'], 'sel')) throw new CompendiumError("Invalid path to page", TRUE, ERRORS::PAGE_ERROR, 401);
		$parent = new Page($_POST['parent'], 'sel');

		if (!$parent->can_edit($user)) throw new CompendiumError("You do not have permission to add to the parent page", TRUE, ERRORS::USER_ERROR, 401);
	}

	$page = Page::create_new_page($user, $title, $description, $text);
	if ($parent !== null) $page->parent = $parent;

	json_ret($page->selector);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code($e->html_response_code);
}

?>