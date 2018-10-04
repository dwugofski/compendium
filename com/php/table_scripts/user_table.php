<?php

include_once(dirname(__DIR__)."..\mysql.php");

function display_usage(){
	echo("\nUsage:\n");
	echo("    php user_table.php <username> <password> [<db = 'compendium'> [<address='127.0.0.1'>]] [-x]\n");
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
			$conn = new MySQLConn($db, $address, $username, $password);

			if ($overwrite){
				$conn->run_query("DROP TABLE IF EXISTS users CASCADE");
			}

			$sql = "
			CREATE TABLE users (
			id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
			username VARCHAR(75) NOT NULL, 
			password VARCHAR(100) NOT NULL,
			email VARCHAR(255) NULL DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX USER (username))
			ENGINE = INNODB";

			$conn->run_query($sql);

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