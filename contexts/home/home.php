<?php

function set_context($dom) {
	$dom->goto("display_h1")->text = "The Compendium";
	$dom->goto("display_h2")->text = "A place where worlds meet";

	$dom->goto("content");
	$dom->append_html(file_get_contents(__DIR__."/home.html"));

	$dom->goto("main");
	$dom->add_class("no-sidebar");

	if (isset($_SESSION['user'])) {
		add_navopt_create($dom);
	}
}

?>