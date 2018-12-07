<?php

include_once(dirname(__DIR__)."../util/mysql.php");

function display_user_record_usage(){
	echo("\nUsage:\n");
	echo("    php user_record_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function user_record_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting user_interactions\n");
			MYSQL::run_query("DROP TABLE IF EXISTS user_interactions CASCADE");
			if ($delete) return;
		}

		echo("Creating user_interactions\n");

		$sql = "
		CREATE TABLE user_interactions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		user_id INT(10) UNSIGNED NOT NULL, 
		page_id INT(10) UNSIGNED DEFAULT NULL, 
		comment_id INT(10) UNSIGNED DEFAULT NULL, 
		interaction_type ENUM('view', 'edit', 'save') NOT NULL, 
		target_type ENUM('page', 'comment') NOT NULL, 
		event DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
		PRIMARY KEY (id), 
		FOREIGN KEY (user_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (page_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (comment_id) REFERENCES comments(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("User record tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish user record tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "user_record_tables.php") !== FALSE) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		display_page_usage();
	}
	else {
		if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
			$overwrite = TRUE;
			$argc -= 1;
			if ($argv[1] == "-xx") $delete = TRUE;
		}

		page_tables($overwrite, $delete);
	}
}

?>