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

	$dom->create("div", ["class"=>"clearer", "id" => "navbar_clearer"], "");
}

function add_navopt($dom, $id, $text="", $right=false, $attrs=null) {
	if (empty($attrs)) $attrs = [];
	if (!isset($text)) $text = "";

	$attrs["id"] = $id;

	$dom->goto("navbar");
	$dom->insert_before("navbar_clearer", "div", $attrs, $text);
	$dom->add_class("navopt");
	$dom->add_class( ($right) ? "fr" : "fl" );
}

function add_navopt_create($dom) {
	add_navopt($dom, "navopt_create", "Create", false, ["parent" => $page_id]);
}


?>