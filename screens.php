<?php

function create_screens($dom){

	$dom->goto("screens");

	if (!isset($_SESSION['user'])) {
		$dom->append_html(file_get_contents(__DIR__."/users/login_screen.html"));
		$dom->end();
	} else {
		$dom->append_html(file_get_contents(__DIR__."/users/delete_screen.html"));
		$dom->end();
	}

	$dom->create("div", ["class"=>"clearer"], "");
}

?>