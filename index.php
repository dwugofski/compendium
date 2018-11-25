<?php

include(__DIR__."/com/php/def/head.php");

$page = "home";
if (isset($_GET["page"])) {
	switch($_GET["page"]) {
		case "home":
		case "view":
			$page = $_GET["page"];
			break;
		case "create":
			if ($loggedin) {
				$page = $_GET["page"];
				break;
			}
		default:
			$page = "home";
	}
}

include(__DIR__."/".$page."/main.php");


?>