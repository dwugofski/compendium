<?php

function create_screens($dom){

	$dom->goto("screens");

	if (!isset($_SESSION['user'])) {
		$dom->append_html(file_get_contents(__DIR__."/../../html/def/screens/login.html"));
		$dom->end();
	} else {
		$dom->append_html(file_get_contents(__DIR__."/../../html/def/screens/delete.html"));
		$dom->end();
	}

	$dom->create("div", ["class"=>"clearer"], "");
}

?>