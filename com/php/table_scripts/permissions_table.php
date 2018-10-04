<?php

include_once(dirname(__DIR__)."..\mysql.php");

function display_usage(){
	echo("\nUsage:\n");
	echo("    php user_table.php [-x]\n");
}

if ($argc){
	$overwrite = FALSE;
	if ($argc > 1 && $argv[$argc - 1] == "-x") {
		$overwrite = TRUE;
		$argc -= 1;
	}

	try{
		if ($overwrite){
			MYSQL::run_query("DROP TABLE IF EXISTS permissions CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS permission_actions CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS user_roles CASCADE");
		}

		$sql = "
		CREATE TABLE permissions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title VARCHAR(255) NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PERMISSION (title))
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE permission_actions (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		description VARCHAR(5) NOT NULL, 
		permission_level INT(10) NOT NULL, 
		PRIMARY KEY (id), 
		INDEX PERMISSION (permission_level), 
		FOREIGN KEY permission_level REFERENCES permissions(id) ON DELETE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$sql = "
		CREATE TABLE user_roles (
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
		permission_level INT(10) NOT NULL, 
		user_id INT(10) NOT NULL, 
		PRIMARY KEY (id), 
		UNIQUE INDEX USER (user_id), 
		FOREIGN KEY permission_level REFERENCES permissions(id) ON DELETE CASCADE, 
		FOREIGN KEY user_id REFERENCES users(id) ON DELETE CASCADE)
		ENGINE = INNODB";
		MYSQL::run_query($sql);

		$permission_levels = [
			'root',
			'admin',
			'user',
			'guest'
		];
		$permission_ids = array();
		$permission_actions = [
			// edit_users_all, view_pages_all, edit_pages_all, edit_pages_open/owned, add_comments, edit_themes, lock_page, view_pages_owned/opened
			['eua', 'epa', 'vpa', 'epo', 'ac', 'et', 'lp', 'vpo'],
			['eua', 'epa', 'vpa', 'epo', 'ac', 'et', 'lp', 'vpo'],
			['epo', 'ac', 'et', 'lp', 'vpo'],
			['vpo']
		];

		$perm = "";
		MYSQL::prepare("INSERT INTO permissions (title) VALUES (?)", 's', [$perm]);
		foreach($permission_level as $i=>$level){
			$perm = $level;
			MYSQL::execute();
			$permission_ids[$i] = MYSQL::get_index();
		}

		$perm = 0;
		$desc = "";
		MYSQL::prepare("INSERT INTO permission_actions (description, permission_level) VALUES (?, ?)", 'si', [$desc, $perm]);
		foreach($permission_id as $i=>$id){
			$perm = $id;
			foreach($permission_actions[$i] as $j=>$action){
				$desc = $action;
				MYSQL::execute();
			}
		}

		echo("Permissions tables configured successfully!\n");

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