<?php

include_once(dirname(__DIR__)."../util/mysql.php");

function display_email_verification_usage(){
	echo("\nUsage:\n");
	echo("    php email_varification_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function email_verification_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			echo("Deleting email_verification_tokens\n");
			MYSQL::run_query("DROP TABLE IF EXISTS email_verification_tokens CASCADE");
			if ($delete) return;
		}

		echo("Creating email_verification_tokens\n");

		$sql = "
		CREATE TABLE email_verification_tokens (
		id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		selector char(24) NOT NULL,
		userid integer(11) UNSIGNED NOT NULL,
		PRIMARY KEY (id),
		INDEX sel (selector),
		FOREIGN KEY (userid) REFERENCES users(id)
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";

		MYSQL::run_query($sql);

		echo("Email verification tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish email verification tables:\n");
		echo($e->getMessage() . "\n");
	}
}

?>