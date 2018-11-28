<?php

function set_context($dom) {
	$dom->goto("display_h1")->text = "Create a Page";
	$dom->goto("display_h2")->text = "Use the form below to create a page";

	$dom->goto("content");
	$dom->append_html(file_get_contents(__DIR__."/../../html/contexts/home.html"));

	$dom->goto("main");
	$dom->add_class("no-sidebar");

	if (isset($_SESSION['user'])) {
		add_navopt_create($dom);
	}
}

?>