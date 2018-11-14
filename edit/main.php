<?php

$sidebar_html = <<<HTML
				<li class="first">The Magician&rsquo;s Nephew</li>
				<li>The Lion, the Witch, and the Wardrobe</li>
				<li class="parent">A Horse and His Boy</li>
				<ul>
					<li class="first">Out of the Silent Planet (The Space Trilogy)</li>
					<li>Perelandra</li>
					<li class="parent">That Hideous Strength</li>
					<ul>
						<li class="first">Subsection 1</li>
						<li>Subsection 2</li>
						<li class="parent">Subsection 3</li>
						<ul>
							<li class="first">Subsubsection 1</li>
							<li>Subsubsection 2</li>
							<li class="parent">Really really really really really long Subsubsection 3</li>
							<ul>
								<li class="first">What is this?</li>
								<li>It&rsquo;s not a section</li>
								<li>Nor a subsection</li>
								<li class="last">Not even a subsubsection</li>
							</ul>
							<li class="restart last">Subsubsection 4</li>
						</ul>
						<li class="restart last">Subsection 1</li>
					</ul>
					<li class="restart">The Great Divorce</li>
					<li>Mere Christianity</li>
					<li class="last">The Screwtape Letters</li>
				</ul>
				<li class="restart">Prince Caspian</li>
				<li>Voyage of the Dawn Treader</li>
				<li>The Silver Chair</li>
				<li class="last">The Last Battle</li>
				<li id="sidebar_footer"></li>
HTML;
$dom->goto("books");
$dom->append_html($sidebar_html);

$dom->goto("display_h1")->text = "Create a Page";
$dom->goto("display_h2")->text = "Use the form below to create a page";

$content_html = <<<HTML
				<div id="text_entry">
					<form id="page_form">
						<input type="text" name="title" id="page_form_title" placeholder="Untitled" />
						<div id="page_form_switch_sub"><span>Preview</span></div>
						<textarea name="text" class="mkdn_editor" id="page_form_text" placeholder="Begin writing here..."></textarea>
						<div id="page_form_preview"></div>
						<div id="page_form_review_sub"><span>Formatting guide</span></div>
						<div id="page_form_submit" class="button">Submit</div>
					</form>
				</div>
HTML;
$dom->goto("content");
$dom->append_html($content_html);

$dom->goto("main");
$dom->add_class("no-sidebar");

echo($dom->html);

?>