<?php

$sidebar_html = <<<HTML
HTML;
$dom->goto("books");
$dom->append_html($sidebar_html);

$dom->goto("display_h1")->text = "Create a Page";
$dom->goto("display_h2")->text = "Use the form below to create a page";

$content_html = <<<HTML
				<div id="text_entry">
					<form id="page_form">
						<input type="hidden" name="user" id="page_form_user" />
						<input type="text" name="title" id="page_form_title" placeholder="Untitled" />
						<input type="text" name="subtitle" id="page_form_subtitle" placeholder="Subtitle" />
						<div id="page_form_switch_sub"><span>Preview</span></div>
						<textarea name="text" class="mkdn_editor" id="page_form_text" placeholder="Begin writing here..."></textarea>
						<div id="page_form_preview"></div>
						<div id="page_form_review_sub"><span>Formatting guide</span></div>
						<div id="page_form_submit" class="button">Submit</div>
					</form>
					<div id="page_form_error" class="error"></div>
				</div>
HTML;
$dom->goto("content");
$dom->append_html($content_html);

$dom->goto("page_form_user")->set_attr("value", $user->selector);

$dom->goto("main");
$dom->add_class("no-sidebar");

echo($dom->html);

?>