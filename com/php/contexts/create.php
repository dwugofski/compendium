<?php

function set_context($dom) {
	$dom->goto("display_h1")->text = "Create a Page";
	$dom->goto("display_h2")->text = "Use the form below to create a page";

	$dom->goto("content");
	$dom->append_html(file_get_contents(__DIR__."/../../html/contexts/create.html"));

	$dom->goto("page_form_user")->set_attr("value", $_SESSION['user']->selector);

	$dom->goto("main");
	$dom->add_class("no-sidebar");
}

?>