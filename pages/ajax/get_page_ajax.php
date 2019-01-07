<?php

include_once(__DIR__."/../../util/session.php");
include_once(__DIR__."/../../errors/errors.php");
include_once(__DIR__."/../page.php");
include_once(__DIR__."/../../users/user.php");
include_once(__DIR__."/../../util/json_head.php");

try {

	$identifier = (isset($_POST["identifier"])) ? $_POST["identifier"] : Page::PRIMARY_KEY;
	if (!array_key_exists($identifier, Page::IDENTIFIERS)) throw new CompendiumError("Cannot find identifier '".$identifier."'", true, ERRORS::PAGE_ERROR);
	if (!isset($_POST['page'])) throw new CompendiumError("Must specify a page.", true, ERRORS::PAGE_ERROR);
	if (!Page::is_page($_POST['page'], $identifier)) throw new CompendiumError("Cannot find a page with '".$identifier."' = '".$_POST['page']."'", true, ERRORS::PAGE_ERROR);
	$page = new Page($_POST['page'], $identifier);
	json_ret($page->data);
}
catch (CompendiumError $e) {
	ERRORS::json_log($e);
	http_response_code(404);
}

?>