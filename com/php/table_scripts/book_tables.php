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
			MYSQL::run_query("DROP TABLE IF EXISTS pages CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS page_colabs CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS page_whitelist CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS sub_pages CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS subjects CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS page_subjects CASCADE");
			if ($delete) return;
		}

		$sql = "
		CREATE TABLE pages (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		content TEXT,
		author_id INT(10) UNSIGNED NOT NULL, 
		locked BOOLEAN DEFAULT FALSE, 
		opened BOOLEAN DEFAULT FALSE, 
		selector CHAR(12) NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PAGE (title), 
		FOREIGN KEY (author_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE page_collabs (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		page_id INT(10) UNSIGNED NOT NULL, 
		collaborator_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PAGE (page_id), 
		FOREIGN KEY (page_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (collaborator_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE page_whitelist (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		color BOOLEAN DEFAULT FALSE, 
		page_id INT(10) UNSIGNED NOT NULL, 
		user_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PAGE (page_id), 
		FOREIGN KEY (page_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (user_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE sub_pages (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		parent_id INT(10) UNSIGNED NOT NULL, 
		child_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PARENT (parent_id), 
		FOREIGN KEY (parent_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (child_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE subjects (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		PRIMARY KEY (id), 
		INDEX SUBJECT (title))
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE page_subjects (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		color BOOLEAN DEFAULT FALSE, 
		page_id INT(10) UNSIGNED NOT NULL, 
		subject_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PAGE (page_id), 
		FOREIGN KEY (page_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (subject_id) REFERENCES subjects(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
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