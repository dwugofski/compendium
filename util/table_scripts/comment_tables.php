<?php

include_once(__DIR__."/../mysql.php");

function display_comment_usage(){
	echo("\nUsage:\n");
	echo("    php comment_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function comment_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting comments\n");
			MYSQL::run_query("DROP TABLE IF EXISTS comments CASCADE");
			if ($delete) return;
		}

		echo("Creating comments\n");

		$sql = "
		CREATE TABLE comments (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		content TEXT,
		author_id INT(10) UNSIGNED DEFAULT NULL, 
		page_id INT(10) UNSIGNED NOT NULL, 
		selector CHAR(24) NOT NULL, 
		created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
		modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
		deleted BOOLEAN DEFAULT FALSE, 
		parent_id INT(10) UNSIGNED DEFAULT NULL, 
		PRIMARY KEY (id), 
		UNIQUE INDEX SEL (selector), 
		FOREIGN KEY (author_id) REFERENCES users(id) 
		ON DELETE SET NULL ON UPDATE CASCADE, 
		FOREIGN KEY (page_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (parent_id) REFERENCES comments(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Comment tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish comment tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "comment_tables.php") !== FALSE) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		display_comment_usage();
	}
	else {
		if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
			$overwrite = TRUE;
			$argc -= 1;
			if ($argv[1] == "-xx") $delete = TRUE;
		}

		comment_tables($overwrite, $delete);
	}
}

?>
