<?php

include_once(dirname(__DIR__)."../mysql.php");
include_once(dirname(__DIR__)."user_tables.php");
include_once(dirname(__DIR__)."permissions_tables.php");
include_once(dirname(__DIR__)."login_tokens_tables.php");
include_once(dirname(__DIR__)."page_tables.php");

function display_tables_usage(){
	echo("\nUsage:\n");
	echo("    php tables.php [-h | --help] [<table name1> ... <tablenameN>] [-x | -xx]\n");
	echo("\n")
	echo("Options:\n");
	echo("    table nameN     Name(s) of the table(s) to create/delete, default to create/delete all tables\n");
	echo("                    Table Names:\n");
	echo("                        user | users            :   User account information\n");
	echo("                        tokens | login_tokens   :   Quick login tokens\n");
	echo("                        permissions             :   Account permissions information\n");
	echo("                        page | pages            :   Page information\n");
	echo("    -x              Overwrite existing tables if they exist\n");
	echo("    -xx             Delete existing tables if they exist, and do not create new ones\n");
	echo("    -h | --help     Display this guide\n");
}

function tables($overwrite, $delete, $tables=NULL) {
	if ($delete || $overwrite) {
		if (empty($tables) || in_array("page", $tables) || in_array("pages", $tables)) page_tables(TRUE, TRUE);
		if (empty($tables) || in_array("permissions", $tables)) permissions_tables(TRUE, TRUE);
		if (empty($tables) || in_array("tokens", $tables) || in_array("login_tokens", $tables)) login_tokens_tables(TRUE, TRUE);
		if (empty($tables) || in_array("user", $tables) || in_array("users", $tables)) login_tokens_tables(TRUE, TRUE);
	}
	if (!$delete) {
		if (empty($tables) || in_array("user", $tables) || in_array("users", $tables)) login_tokens_tables(FALSE, FALSE);
		if (empty($tables) || in_array("tokens", $tables) || in_array("login_tokens", $tables)) login_tokens_tables(FALSE, FALSE);
		if (empty($tables) || in_array("permissions", $tables)) permissions_tables(FALSE, FALSE);
		if (empty($tables) || in_array("page", $tables) || in_array("pages", $tables)) page_tables(FALSE, FALSE);
	}
}

if ($argc && strpos($argv[0], "tables.php") >= 0) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		display_tables_usage();
	}
	else {
		if ($argc > 1 && ($argv[$argc - 1] == "-x" ||  $argv[$argc - 1] == "-xx")) {
			$overwrite = TRUE;
			if ($argv[$argc - 1] == "-xx") $delete = TRUE;
			$argc -= 1;
		}
		$tables = array();
		for ($i = $argc; $i > 0; $i -= 1) {
			$tables[] = $argv[$i];
		}
		tables($overwrite, $delete, $tables);
	}
}

?>