<?php

include_once(dirname(__DIR__)."..\mysql.php");

function display_usage(){
	echo("\nUsage:\n");
	echo("    php login_tokens_table.php <username> <password> [<db = 'compendium'> [<address='127.0.0.1'>]] [-x]\n");
}

if ($argc){
	$overwrite = FALSE;
	if ($argc > 1 && $argv[$argc - 1] == "-x") {
		$overwrite = TRUE;
		$argc -= 1;
	}
	if ($argc >= 3) {
		$username = $argv[1];
		$password = $argv[2];
		$db = ($argc >= 4) ? $argv[3] : 'compendium';
		$address = ($argc >= 5) ? $argv[4] : '127.0.0.1';

		try{
			echo(sprintf("Making a connection with username='%s', password='%s' to '%s' :: '%s' with deletion set to %s\n", $username, $password, $address, $db, $overwrite ? "true" : "false"));
			MYSQL::init($db, $address, $username, $password);

			if ($overwrite){
				MYSQL::run_query("DROP TABLE IF EXISTS user_tokens CASCADE");
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
}
else display_usage();

?>