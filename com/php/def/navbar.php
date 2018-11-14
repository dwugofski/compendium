<?php

$dom->goto("navbar");

$dom->create("div", ["class"=>"navopt fl"], "Compendium Home");

if ($loggedin) {
	$dom->append_html(get_navopt_user());
	$dom->end();
} else {
	$dom->append_html(get_navopt_sign_in());
	$dom->end();
}

$dom->create("div", ["class"=>"clearer"], "");

function get_navopt_user() {
	$html = <<<HTML
		<div class="navopt dropdown fr">
			<span class="dropdown-toggle" type="button" id="navopt_user" data-toggle="dropdown">Hello, <?=$user->username?> &#9660;</span>
			<ul class="dropdown-menu dropdown-menu-right">
				<li>Create a page</li>
				<li class="spacer"></li>
				<li id="navopt_dd_logout">Log Out</li>
			</ul>
		</div>
HTML;
		return $html;
}

function get_navopt_sign_in() {
	$html = <<<HTML
		<div class="navopt fr" id="navopt_sign_in">Log In / Sign Up</div>
HTML;
		return $html;
}

?>