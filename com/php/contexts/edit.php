<?php

function set_context($dom) {
	if (!isset($_SESSION['user'])) throw new CompendiumError("Must be logged in.", false, ERRORS::USER_ERROR, 401);
	if (!isset($_GET['page_id'])) throw new CompendiumError("Must specify a page.", false, ERRORS::PAGE_ERROR, 404);
	if (!Page::is_page($_GET['page_id'], "selector")) throw new CompendiumError("Page not found", false, ERRORS::PAGE_ERROR, 404);
	$page = new Page($_GET['page_id'], "selector");

	$dom->goto("main");
	$dom->add_class("no-sidebar");

	$dom->goto("display_h1")->text = "Edit a Page";
	$dom->goto("display_h2")->text = "Use the form below to edit this page";

	$dom->goto("content");
	$dom->append_html(file_get_contents(__DIR__."/../../html/contexts/create.html"));
	$dom->goto("text_entry")->set_attr("id", "text_editing");

	$dom->goto("page_form_user")->set_attr("value", $_SESSION['user']->selector);

	if ($page->has_parent()) {
		$dom->goto("page_form_path")->text = $page->parent->path['titles'];
		$dom->goto("page_form_parent")->set_attr("value", $page->parent->selector);
	}
}


?>