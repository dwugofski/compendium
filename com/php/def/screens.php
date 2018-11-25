<?php

$dom->goto("screens");

if (!$loggedin) {
	$dom->append_html(get_login_screen());
	$dom->end();
} else {
	$dom->append_html(get_user_delete_screen());
	$dom->end();
}

$dom->create("div", ["class"=>"clearer"], "");

function get_login_screen() {
	$html = <<<HTML
	<div id="login_screen" class="screen">
		<div id="login">
			<h1>Access the Compendium</h1>
			<form id="login_form">
				<label>Username or email address</label>
				<div class="ttb">
					<input id="login_form_username" type="text" name="username"/>
					<div class="tt">
						Username must
						<ul>
							<li>Be between 3-100 characters</li>
							<li>Start with a letter</li>
							<li>Contain only letters, numbers, or underscores</li>
						</ul>
					</div>
				</div>
				<label>Password</label>
				<div class="ttb">
					<input id="login_form_password" type="password" name="password"/>
					<div class="tt">
						Password must
						<ul>
							<li>Be between 8-100 characters</li>
							<li>Contain only letters, numbers, spaces, or special characters</li>
						</ul>
					</div>
				</div>
				<div id="login_form_submit" class="button disabled">Sign in</div>
				<hr/>
				<h2>New to Compendium?<br/><a id="login_sign_up">Sign Up</a></h2>
			</form>
			<form id="signup_form">
				<label>Username</label>
				<div class="ttb">
					<input id="signup_form_username" type="text" name="username"/>
					<div class="tt">
						Username must
						<ul>
							<li>Be between 3-100 characters</li>
							<li>Start with a letter</li>
							<li>Contain only letters, numbers, and underscores</li>
						</ul>
					</div>
				</div>
				<label>Email Address</label>
				<input id="signup_form_email" type="text" name="email"/>
				<label>Password</label>
				<div class="ttb">
					<input id="signup_form_password1" type="password" name="password1"/>
					<div class="tt">
						Password must
						<ul>
							<li>Be between 8-100 characters</li>
							<li>Contain only letters, numbers, spaces, and special characters</li>
						</ul>
					</div>
				</div>
				<label>Password (confirm)</label>
				<input id="signup_form_password2" type="password" name="password2"/>
				<div id="signup_form_submit" class="button">Sign up</div>
				<hr/>
				<h2>Already a user?<br/><a id="signup_log_in">Sign In</a></h2>
			</form>
			<div id="login_error" class="error">This is an error message</div>
		</div>
	</div>
HTML;
		return $html;
}

function get_user_delete_screen() {
	$html = <<<HTML
	<div id="user_delete_screen" class="screen">
		<div id="user_delete">
			<h1>Delete Your Account</h1>
			<form id="user_delete_form">
				<label>Please enter your password</label>
				<div class="ttb">
					<input id="user_delete_form_password" type="password" name="password"/>
					<div class="tt">
						Password must
						<ul>
							<li>Be between 8-100 characters</li>
							<li>Contain only letters, numbers, spaces, or special characters</li>
						</ul>
					</div>
				</div>
				<div class="error" style="display: block;">WARNING: This action is permanent</div>
				<div id="user_delete_form_submit" class="button">Delete Account</div>
			</form>
			<div id="user_delete_error" class="error">This is an error message</div>
		</div>
	</div>
HTML;
		return $html;
}

?>