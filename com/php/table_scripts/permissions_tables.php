<?php

include_once(dirname(__DIR__)."../mysql.php");
include_once(dirname(__DIR__)."../user.php");

function display_permissions_usage(){
	echo("\nUsage:\n");
	echo("    php permissions_tables.php [-x | -xx]\n");
	echo("\n");
	echo("Options:\n");
	echo("    -x    Overwrite existing tables if they exist\n");
	echo("    -xx   Delete existing tables if they exist, and do not create new ones\n");
}

function permissions_tables($overwrite, $delete) {
	try{
		if ($overwrite){
			MYSQL::run_query("DROP TABLE IF EXISTS user_roles CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS permission_actions CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS permissions CASCADE");
			if ($delete) return;
		}

		$permission_levels = [
			User::PERM_ROOT,
			User::PERM_ADMIN,
			User::PERM_USER,
			User::PERM_GUEST
		];
		$permission_ids = array();
		$permission_actions = [
			[User::ACT_EDIT_ALL_ADMINS, User::ACT_EDIT_ALL_USERS, User::ACT_EDIT_ALL_THEMES, User::ACT_EDIT_ALL_PAGES, User::ACT_LOCK_ALL_PAGES, User::ACT_OPEN_ALL_PAGES, User::ACT_EDIT_ALL_COMMENTS, User::ACT_ADD_ALL_COMMENTS, User::ACT_VIEW_ALL_PAGES, User::ACT_EDIT_OWN_USER, User::ACT_EDIT_OWN_PAGES, User::ACT_LOCK_OWN_PAGES, User::ACT_OPEN_OWN_PAGES, User::ACT_EDIT_OWN_COMMENTS, User::ACT_ADD_OWN_COMMENTS, User::ACT_VIEW_OWN_PAGES, User::ACT_EDIT_OPEN_PAGES, User::ACT_ADD_OPEN_COMMENTS, User::ACT_VIEW_UNLOCKED_PAGES],
			[User::ACT_EDIT_ALL_USERS, User::ACT_EDIT_ALL_THEMES, User::ACT_EDIT_ALL_PAGES, User::ACT_LOCK_ALL_PAGES, User::ACT_OPEN_ALL_PAGES, User::ACT_EDIT_ALL_COMMENTS, User::ACT_ADD_ALL_COMMENTS, User::ACT_VIEW_ALL_PAGES, User::ACT_EDIT_OWN_USER, User::ACT_EDIT_OWN_PAGES, User::ACT_LOCK_OWN_PAGES, User::ACT_OPEN_OWN_PAGES, User::ACT_EDIT_OWN_COMMENTS, User::ACT_ADD_OWN_COMMENTS, User::ACT_VIEW_OWN_PAGES, User::ACT_EDIT_OPEN_PAGES, User::ACT_ADD_OPEN_COMMENTS, User::ACT_VIEW_UNLOCKED_PAGES],
			[User::ACT_EDIT_OWN_USER, User::ACT_EDIT_OWN_PAGES, User::ACT_LOCK_OWN_PAGES, User::ACT_OPEN_OWN_PAGES, User::ACT_EDIT_OWN_COMMENTS, User::ACT_ADD_OWN_COMMENTS, User::ACT_VIEW_OWN_PAGES, User::ACT_EDIT_OPEN_PAGES, User::ACT_ADD_OPEN_COMMENTS, User::ACT_VIEW_UNLOCKED_PAGES],
			[User::ACT_VIEW_UNLOCKED_PAGES],
		];

		echo("Creating permissions\n");

		$sql = "
		CREATE TABLE permissions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PERMISSION (title))
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Logging permissions\n");

		$perm = "";
		MYSQL::prepare("INSERT INTO permissions (title) VALUES (?)", 's', [&$perm]);
		foreach($permission_levels as $i=>$level){
			$perm = $level;
			MYSQL::execute();
			$permission_ids[] = MYSQL::get_index();
		}

		echo("Creating permission_actions\n");

		$sql = "
		CREATE TABLE permission_actions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		description VARCHAR(5) NOT NULL, 
		permission_level INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		FOREIGN KEY (permission_level) REFERENCES permissions(id)
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Assigning actions\n");

		$perm = 0;
		$desc = "";
		MYSQL::prepare("INSERT INTO permission_actions (description, permission_level) VALUES (?, ?)", 'si', [&$desc, &$perm]);
		foreach($permission_ids as $i=>$id){
			$perm = $id;
			foreach($permission_actions[$i] as $j=>$action){
				$desc = $action;
				MYSQL::execute();
			}
		}

		echo("Creating user_roles\n");

		$sql = "
		CREATE TABLE user_roles (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		permission_level INT(10) UNSIGNED NOT NULL, 
		user_id INT(10) UNSIGNED NOT NULL, 
		PRIMARY KEY (id), 
		UNIQUE INDEX USER (user_id), 
		FOREIGN KEY (permission_level) REFERENCES permissions(id)
		ON DELETE CASCADE ON UPDATE CASCADE, 
		FOREIGN KEY (user_id) REFERENCES users(id) 
		ON DELETE CASCADE ON UPDATE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		echo("Permissions tables configured successfully!\n");
	}
	catch(Exception $e){
		echo("Error occurred in trying to establish permissions tables:\n");
		echo($e->getMessage() . "\n");
	}
}

if ($argc && strpos($argv[0], "permissions_tables.php") >= 0) {
	$overwrite = FALSE;
	$delete = FALSE;
	if ($argc > 1 && $argv[1] == "-h" || $argv[1] == "--help") {
		display_permissions_usage();
	}
	else {
		if ($argc > 1 && ($argv[1] == "-x" ||  $argv[1] == "-xx")) {
			$overwrite = TRUE;
			$argc -= 1;
			if ($argv[1] == "-xx") $delete = TRUE;
		}

		permissions_tables($overwrite, $delete);
	}
}

?>