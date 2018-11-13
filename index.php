<?php

include_once(__DIR__."/com/php/user.php");
include_once(__DIR__."/com/php/session.php");

$loggedin = false;
$user = NULL;

error_log($_SESSION['user']->username);

if(!empty($_SESSION['user'])) {
	$loggedin = true;
	$user = $_SESSION['user'];
} else $loggedin = false;

?>

<!DOCTYPE html>
<html>
	<!--<link rel="stylesheet" href="com/js/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="com/js/bootstrap/css/bootstrap-theme.min.css">-->
	<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

	<link rel="stylesheet" type="text/css" href="com/css/consts/default_colors.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/consts/default_fonts.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/consts/sizes.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/main.css"/>

	<!--<script src="com/js/jquery.js"></script>-->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

	<!--<script src="com/js/bootstrap/bootstrap.min.js"></script>-->
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	<script src="com/js/slate/immutable.min.js"></script>
	<script src="com/js/slate/react.production.min.js"></script>
	<script src="com/js/slate/react-dom.production.min.js"></script>
	<script src="com/js/slate/react-dom-server.browser.production.min.js"></script>
	<script src="https://unpkg.com/slate/dist/slate.js"></script>
	<script src="https://unpkg.com/slate-react/dist/slate-react.js"></script>

	<script src="com/js/showdown/showdown.min.js"></script>
	<!--<script src="https://unpkg.com/slate@0.43.7/lib/slate.js"></script>
	<script src="https://unpkg.com/slate-react@0.21.4/lib/slate-react.js"></script>-->
	<script type="module" src="com/js/def.js"></script>
	<!--<script type="module" src="com/js/def/cookies.js"></script>
	<script type="module" src="com/js/def/login.js"></script>
	<script type="module" src="com/js/def/head.js"></script>
	<script type="module" src="com/js/def/head/rendering.js"></script>
	<script type="module" src="com/js/def/edit.js"></script>
	<script type="module" src="com/js/def/edit/editor.js"></script>-->
	<script>
		console.log("<?=$_SESSION['user']->username?>");
	</script>

<head>
</head>
<body>
	<div id="header">
		<h1>The Compendium</h1>
	</div>

	<div id="navbar">

		<div class="navopt fl">Compendium Home</div>

<?php
if ($loggedin) {?>
		<div class="navopt dropdown fr">
				<span class="dropdown-toggle" type="button" id="navopt_user" data-toggle="dropdown">Hello, <?=$user->username?> &#9660;</span>
				<ul class="dropdown-menu dropdown-menu-right">
    				<li>Create a page</li>
					<li id="navopt_dd_logout">Log Out</li>
				</ul>
		</div>
<?php
} else {
?>
		<div class="navopt fr" id="navopt_sign_in">Log In / Sign Up</div>
<?php
}
?>		
		<div class="clearer"></div>
	</div>
	<div class="clearer">
	</div>

	<div id="main">
		<div id="sidebar">
			<ul id="books">
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
			</ul>
		</div>
		<div id="display">
			<div id="heading">
				<h1>The Compendium</h1>
				<h2>A place where worlds meet</h2>
			</div>

			<div id="content">
				<div id="text_entry">
					<form id="page_form">
						<input type="text" name="title" id="page_form_title" placeholder="Untitled" />
						<div id="page_form_switch_sub"><span>Preview</span></div>
						<textarea name="text" class="mkdn_editor" id="page_form_text" placeholder="Begin writing here..."></textarea>
						<div id="page_form_preview"></div>
						<!--<div id="page_form_text"></div>-->
						<div id="page_form_review_sub"><span>Formatting guide</span></div>
						<div id="page_form_submit" class="button">Submit</div>
					</form>
				</div>
				<!--
				<h1>Chapter 1: Lorem</h1>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h2>Section 1: Ipsum:</h2>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h3>Dolor:</h3>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h4>Sit:</h4>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h5>Amet</h5>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<h6>Adipiscing</h6>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel.
				</p>
				<p>
					Lorem ipsum dolor sit amet, <i>consectetur</i> adipiscing elit. Nullam <em>interdum</em> facilisis tempor. Maecenas volutpat nisi enim, vel porttitor nunc blandit vel. Integer quis dictum diam. <b>Phasellus</b> tempus ante sed quam congue, sed pulvinar massa pretium. Integer sed commodo lorem, eget gravida purus. <a href="/foobar">Aenean</a> vitae <a href="">metus</a> at enim vulputate facilisis ac nec velit. Ut mattis accumsan nunc non lacinia. Donec dapibus sem dignissim fermentum bibendum. Praesent sodales quam in lorem scelerisque porttitor. Morbi sit amet lectus et augue molestie vulputate non sit amet tortor. Vestibulum facilisis commodo euismod. Donec turpis felis, mollis nec feugiat at, vestibulum non enim.
				</p>
				<p>
					Pellentesque congue quam et sapien porttitor, ultrices dignissim metus pretium. Aliquam vel lorem semper, bibendum justo sed, vulputate dolor. Aliquam vulputate tincidunt libero, eu dignissim tellus sagittis non. Quisque condimentum, nulla et eleifend eleifend, enim odio sagittis odio, at varius metus risus nec odio. Etiam sagittis et diam vitae gravida. Ut nec euismod massa, quis aliquet odio. Nunc a sem ut nisi gravida elementum. Vestibulum lobortis tincidunt posuere. Praesent dictum metus sit amet magna faucibus bibendum. Nunc luctus metus imperdiet maximus auctor.
				</p>
				<p>
					Sed augue nisi, condimentum sit amet nunc quis, tempus mattis ligula. Vivamus sagittis ipsum urna, ut feugiat ex ullamcorper eu. Nam vitae nunc ac risus rutrum placerat at nec quam. Cras mollis diam sed pretium laoreet. Pellentesque eget pellentesque felis. Ut vitae massa a tortor tempus consequat. Praesent dignissim velit est, sit amet ultrices dui pharetra eget. Ut ullamcorper euismod sodales.
				</p>
				<p>
					Cras ornare consectetur libero vel posuere. Fusce suscipit ac leo vitae ullamcorper. In blandit vehicula ex, eu commodo magna semper nec. Proin fermentum mollis dolor a gravida. Sed porttitor magna felis. Nunc pellentesque mollis aliquet. Mauris dignissim augue non suscipit congue. Vivamus egestas ex vel libero rhoncus, fermentum vestibulum lectus aliquam. Pellentesque a pharetra leo. Ut eget metus dapibus, pulvinar nibh et, ultricies nulla. Vivamus odio risus, volutpat at gravida vel, laoreet eget augue. Nullam id est nisl. Proin tempus urna ut vehicula vestibulum. Nullam leo diam, ultricies eget massa ut, posuere placerat turpis. Nam accumsan lectus sit amet felis hendrerit molestie. Etiam ut ligula ut tellus congue gravida.
				</p>
				-->
			</div>
		</div>
		<div class="clearer">
		</div>
	</div>
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
				<input id="signup_form_username" type="text" name="username"/>
				<label>Email Address</label>
				<input id="signup_form_email" type="text" name="email"/>
				<label>Password</label>
				<input id="signup_form_password1" type="password" name="password1"/>
				<label>Password (confirm)</label>
				<input id="signup_form_password2" type="password" name="password2"/>
				<div id="signup_form_submit" class="button">Sign up</div>
				<hr/>
				<h2>Already a user?<br/><a id="signup_log_in">Sign In</a></h2>
			</form>
			<div id="login_error" class="error">This is an error message</div>
		</div>
	</div>
</body>
</html>