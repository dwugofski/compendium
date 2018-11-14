<?php

include(__DIR__."/com/php/def/head.php");

$page = "home";
if (isset($_GET["page"])) {
	switch($_GET["page"]) {
		case "home":
		case "edit":
		case "view":
			$page = $_GET["page"];
			break;
		default:
			$page = "home";
	}
}

include(__DIR__."/".$page."/main.php");


?>