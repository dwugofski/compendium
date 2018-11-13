<?php

include_once(dirname(__DIR__)."../util/mysql.php");

function display_user_usage(){
	echo("\nUsage:\n");
	echo("    php user_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function user_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting users\n");
			MYSQL::run_query("DROP TABLE IF EXISTS users CASCADE");
			if ($delete) return;
		}

		echo("Creating users\n");

		$sql = "
		CREATE TABLE users (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		username VARCHAR(75) NOT NULL, 
		password VARCHAR(100) NOT NULL,
		email VARCHAR(255) NULL DEFAULT NULL,
		selector CHAR(24) NOT NULL, 
		PRIMARY KEY (id),
		INDEX USER (username), 
		UNIQUE INDEX SEL (selector))
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Users table configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish users tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "user_tables.php") !== FALSE) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		display_user_usage();
	}
	else {
		if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
			$overwrite = TRUE;
			$argc -= 1;
			if ($argv[1] == "-xx") $delete = TRUE;
		}

		user_tables($overwrite, $delete);
	}
}

?>