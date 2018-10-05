<?php

include_once(dirname(__DIR__)."..\mysql.php");

function display_usage(){
	echo("\nUsage:\n");
	echo("    php user_table.php [-x | -xx]\n");
}

if ($argc){
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
		$overwrite = TRUE;
		$argc -= 1;
		if ($argv[1] == "-xx") $delete = TRUE;
	}

	try{
		if ($overwrite){
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
		PRIMARY KEY (id),
		INDEX USER (username))
		ENGINE = INNODB";
		MYSQL::run_query($sql);
		echo("SQL query run successfully!\n");

		// Steps:
		// 1. Check if table exists
		// 2. If table does not exist, create table
		//    2.a If table does exist, overwrite if specified
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish table:\n");
		echo($e->getMessage() . "\n");
	}
}
else display_usage();

?>