<?php

include_once(__DIR__."/com/php/def/def.php");

$loggedin = false;
if(!empty($_SESSION['user'])) {
	$loggedin = true;
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
set_context($dom);

echo $dom->print();

?>