<?php

include_once(__DIR__."/com/php/def/def.php");

$loggedin = false;
if(!empty($_SESSION['user'])) {
	try {
		if (User::is_user($_SESSION['user']->id)) $loggedin = true;
		else {
			$loggedin = false;
			unset($_SESSION['user']);
		}
	} catch (Exception $e) {
		unset($_SESSION['user']);
		$loggedin = false;
	}
} else $loggedin = false;

$html = file_get_contents(__DIR__."/com/html/index.html");

$dom = new MyDOM($html);

$context = "home";
if (isset($_GET["context"])) {
	switch($_GET["context"]) {
		case "home":
		case "view":
			$context = $_GET["context"];
			break;
		case "create":
		case "edit":
			if ($loggedin) {
				$context = $_GET["context"];
				break;
			}
		default:
			$context = "home";
	}
}

include(__DIR__."/com/php/contexts/".$context.".php");

create_navbar($dom);
create_screens($dom);

try {
	set_context($dom);
} catch (CompendiumError $e) {
	$dom->goto("main")->add_class("no-sidebar");
	$dom->goto("display_h1")->text = "Something Went Wrong";
	$dom->goto("display_h2")->text = "The Compendium has encountered an error";

	$dom->goto("content")->clear();

	if (file_exists(__DIR__."/com/html/errors/".$e->html_response_code.".html")) {
		$dom->append_html(file_get_contents(__DIR__."/com/html/errors/".$e->html_response_code.".html"));
	} else {
		ERRORS::log(ERRORS::UNKNOWN_ERROR, "Could not service error code ".$e->html_response_code);
		$dom->append_html(file_get_contents(__DIR__."/com/html/errors/500.html"));
	}
}

echo $dom->print();

?>