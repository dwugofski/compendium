<?php

include_once(__DIR__."/../user/user.php");
include_once(__DIR__."/../util/session.php");
include_once(__DIR__."/../util/dom.php");

$loggedin = false;
$user = NULL;

if(!empty($_SESSION['user'])) {
	$loggedin = true;
	$user = $_SESSION['user'];
} else $loggedin = false;

$html = <<<HTML

<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

	<link rel="stylesheet" type="text/css" href="com/css/consts/default_colors.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/consts/default_fonts.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/consts/sizes.css"/>
	<link rel="stylesheet" type="text/css" href="com/css/main.css"/>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	<script src="com/js/slate/immutable.min.js"></script>
	<script src="com/js/slate/react.production.min.js"></script>
	<script src="com/js/slate/react-dom.production.min.js"></script>
	<script src="com/js/slate/react-dom-server.browser.production.min.js"></script>
	<script src="https://unpkg.com/slate/dist/slate.js"></script>
	<script src="https://unpkg.com/slate-react/dist/slate-react.js"></script>

	<script src="com/js/showdown/showdown.min.js"></script>
	<script type="module" src="com/js/def.js"></script>
</head>
<body>
	<div id="header">
		<h1>The Compendium</h1>
	</div>
	<div id="navbar"></div>
	<div class="clearer"></div>
	<div id="main">
		<div id="sidebar">
			<ul id="books"></ul>
		</div>
		<div id="display">
			<div id="heading">
				<h1 id="display_h1"></h1>
				<h2 id="display_h2"></h2>
			</div>
			<div id="content"></div>
		</div>
		<div class="clearer">
		</div>
	</div>
	<div id="screens">
	</div>
</body>
</html>

HTML;

$dom = new MyDOM($html);

include_once(__DIR__."/navbar.php");
include_once(__DIR__."/screens.php");

?>