<?php

function create_navbar($dom) {
	$dom->goto("navbar");

	$dom->create("div", ["class"=>"navopt fl", "id"=>"navopt_home"], "Compendium Home");

	if (isset($_SESSION['user'])) {
		$dom->append_html(file_get_contents(__DIR__."/../../html/def/navbar/navopt_user.html"));
		$dom->goto("navopt_user")->text = "Hello, ".$_SESSION['user']->username." \u{25BC}";
		$dom->goto("navbar");
	} else {
		$dom->append_html(file_get_contents(__DIR__."/../../html/def/navbar/navopt_sign_in.html"));
		$dom->end();
	}

	$dom->create("div", ["class"=>"clearer"], "");
}



?>