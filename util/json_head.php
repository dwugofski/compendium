<?php

include_once(__DIR__."/../errors/errors.php");

function json_ret($output=NULL) {
	echo(json_encode($output));
	die();
}


?>