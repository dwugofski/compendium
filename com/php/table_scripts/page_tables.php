<?php

include_once(dirname(__DIR__)."../util/mysql.php");

function display_page_usage(){
	echo("\nUsage:\n");
	echo("    php page_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function page_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting page_subjects\n");
			MYSQL::run_query("DROP TABLE IF EXISTS page_subjects CASCADE");
			echo("Deleting subjects\n");
			MYSQL::run_query("DROP TABLE IF EXISTS subjects CASCADE");
			echo("Deleting sub_pages\n");
			MYSQL::run_query("DROP TABLE IF EXISTS sub_pages CASCADE");
			echo("Deleting page_whitelists\n");
			MYSQL::run_query("DROP TABLE IF EXISTS page_whitelists CASCADE");
			echo("Deleting page_colabs\n");
			MYSQL::run_query("DROP TABLE IF EXISTS page_colabs CASCADE");
			echo("Deleting pages\n");
			MYSQL::run_query("DROP TABLE IF EXISTS pages CASCADE");
			if ($delete) return;
		}

		echo("Creating pages\n");

		$sql = "
		CREATE TABLE pages (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		description VARCHAR(255) NOT NULL, 
		content TEXT,
		author_id INT(10) UNSIGNED NOT NULL, 
		locked BOOLEAN DEFAULT FALSE, 
		opened BOOLEAN DEFAULT FALSE, 
		selector CHAR(24) NOT NULL, 
		created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
		modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, 
		parent_id INT(10) UNSIGNED DEFAULT NULL, 
		PRIMARY KEY (id), 
		INDEX PAGE (title), 
		UNIQUE INDEX SEL (selector), 
		FOREIGN KEY (author_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (parent_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Creating page_colabs\n");

		$sql = "
		CREATE TABLE page_colabs (
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

		echo("Creating page_whitelists\n");

		$sql = "
		CREATE TABLE page_whitelists (
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

		echo("Creating subjects\n");

		$sql = "
		CREATE TABLE subjects (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		book_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		INDEX SUBJECT (title), 
		FOREIGN KEY (book_id) REFERENCES pages(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Creating page_subjects\n");

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

		echo("Page tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish pages tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "page_tables.php") !== FALSE) {
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