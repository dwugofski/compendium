<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../user/user.php");
include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/page.php");
include_once(__DIR__."/../util/json_head.php");

try {
	if (!isset($_POST["page"])) throw new CompendiumError("Must specify a page to edit", true, ERRORS::PAGE_ERROR, 404);
	$title = (isset($_POST["title"])) ? $_POST["title"] : "";
	$description = (isset($_POST["description"])) ? $_POST["description"] : "";
	$text = $_POST["text"];
	$page_id = $_POST["page"];
	$user = null;

	if (!Page::is_page($page_id, "selector")) throw new CompendiumError("Page with selector '".$page_id."' not found", true, ERRORS::PAGE_ERROR, 404);
	$page = new Page($page_id, "selector");

	if (!isset($_SESSION['user'])) throw new CompendiumError("Must be logged in to edit pages", true, ERRORS::USER_ERROR, 401);
	$user = $_SESSION['user'];
	if (!User::is_user($user->id)) throw new CompendiumError("User not recognized", true, ERRORS::USER_ERROR, 401);

	if ($page->can_edit($user) == FALSE) throw new CompendiumError("User does not have permission to edit this page", TRUE, ERRORS::USER_ERROR, 401);
	if (Page::validate_title($title) == FALSE) throw new CompendiumError("Title is not valid", TRUE, ERRORS::PAGE_ERROR, 400);
	if (Page::validate_description($description) == FALSE) throw new CompendiumError("Subtitle is not valid", TRUE, ERRORS::PAGE_ERROR, 400);
	if (Page::validate_text($text)   == FALSE) throw new CompendiumError("Text is not valid", TRUE, ERRORS::PAGE_ERROR, 400);

	$page->title = $title;
	$page->description = $description;
	$page->text = $text;

	json_ret($page->selector);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code($e->html_response_code);
}

?>