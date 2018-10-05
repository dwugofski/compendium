<?php

include_once(dirname(__DIR__)."..\mysql.php");

function display_usage(){
	echo("\nUsage:\n");
	echo("    php login_tokens_table.php [-x | -xx]\n");
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
			MYSQL::run_query("DROP TABLE IF EXISTS login_tokens CASCADE");
			if ($delete) return;
		}

		$sql = "
		CREATE TABLE login_tokens (
		id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		selector char(12) NOT NULL,
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