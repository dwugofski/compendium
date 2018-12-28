<?php

include_once(__DIR__."/../util/mysql.php");

function display_tokens_usage(){
	echo("\nUsage:\n");
	echo("    php login_tokens_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function login_tokens_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting login_tokens\n");
			MYSQL::run_query("DROP TABLE IF EXISTS login_tokens CASCADE");
			if ($delete) return;
		}

		echo("Creating login_tokens\n");

		$sql = "
		CREATE TABLE login_tokens (
		id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		selector char(24) NOT NULL,
		valhash char(128) NOT NULL,
		userid integer(11) UNSIGNED NOT NULL,
		expires datetime NOT NULL,
		PRIMARY KEY (id),
		INDEX sel (selector),
		INDEX user (userid),
		CONSTRAINT fk_user FOREIGN KEY (userid)
		REFERENCES users(id)
		ON DELETE CASCADE
		ON UPDATE CASCADE)
		ENGINE = INNODB";

		MYSQL::run_query($sql);

		echo("Login tokens tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish login tokens tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "login_tokens_tables.php") !== FALSE) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		login_tokens_usage();
	}
	else {
		if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
			$overwrite = TRUE;
			$argc -= 1;
			if ($argv[1] == "-xx") $delete = TRUE;
		}

		login_tokens_tables($overwrite, $delete);
	}
}

?>