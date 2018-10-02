<?php

include_once("../mysql.php");

function display_usage(){
	echo("php user_table.php [-x] <username> <password> [<db = 'compendium'> [<address='127.0.0.1'>]]")
}

$conn = new MySQLConn("127.0.0.1", "root", "")

if ($argc){
	$overwrite = FALSE
	if ($argc > 1 && $argv[2] == "-x") $overwrite = TRUE;
	if ($argc >= 3) {
		$username = $argv[1];
		$password = $argv[2];
		$db = ($argc >= 4) ? $argv[3] : 'compendium';
		$address = ($argc >= 5) ? $argv[4] : '127.0.0.1';

		try{
			$conn = new MySQLConn($db, $address, $username, $password);
			$sql = ""; //<<<'SQL'
				 // SQL Code goes here
//SQL;
			$conn->run_query($sql);

			// Steps:
			// 1. Check if table exists
			// 2. If table does not exist, create table
			//    2.a If table does exist, overwrite if specified
		}
		catch(MySQLError $e){
			echo("Error occurred in trying to establish table:\n");
			echo($e->message . "\n");
		}
	}
	else display_usage();
}
else display_usage();

?>