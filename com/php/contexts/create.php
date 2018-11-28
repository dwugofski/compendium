<?php

function set_context($dom) {
	if (!isset($_SESSION['user'])) throw new CompendiumError("Must be logged in.", FALSE, ERRORS::USER_ERROR, 403);

	$dom->goto("main");
	$dom->add_class("no-sidebar");

	$dom->goto("display_h1")->text = "Create a Page";
	$dom->goto("display_h2")->text = "Use the form below to create a page";

	$dom->goto("content");
	$dom->append_html(file_get_contents(__DIR__."/../../html/contexts/create.html"));

	$dom->goto("page_form_user")->set_attr("value", $_SESSION['user']->selector);

	if (isset($_GET['page_id'])) {
		if (Page::is_page_selector($_GET['page_id'])) {
			$parent = Page::get_page_from_sel($_GET['page_id']);
			$dom->goto("page_form_path")->text = $parent->path['titles'];
			$dom->goto("page_form_parent")->set_attr("value", $parent->selector);
		} else throw new CompendiumError("Parent page not found", FALSE, ERRORS::USER_ERROR, 404);
	} else {
		$dom->goto("page_form_path")->text = "Select a parent book";
	}
}

?>