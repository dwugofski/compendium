<?php

$dom->goto("navbar");

$dom->create("div", ["class"=>"navopt fl"], "Compendium Home");

if ($loggedin) {
	$dom->append_html(get_navopt_user($user->username));
	$dom->end();
} else {
	$dom->append_html(get_navopt_sign_in());
	$dom->end();
}

$dom->create("div", ["class"=>"clearer"], "");

function get_navopt_user($username) {
	$html = <<<HTML
		<div class="navopt dropdown fr">
			<span class="dropdown-toggle" type="button" id="navopt_user" data-toggle="dropdown">Hello, $username &#9660;</span>
			<ul class="dropdown-menu dropdown-menu-right">
				<li id="navopt_dd_create">Create a page</li>
				<li class="spacer"></li>
				<li id="navopt_dd_logout">Log Out</li>
				<li id="navopt_dd_user_delete">Delete Account</li>
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