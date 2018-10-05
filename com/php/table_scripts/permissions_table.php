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
			MYSQL::run_query("DROP TABLE IF EXISTS user_roles CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS permission_actions CASCADE");
			MYSQL::run_query("DROP TABLE IF EXISTS permissions CASCADE");
			if ($delete) return;
		}

		$permission_levels = [
			'root',
			'admin',
			'user',
			'guest'
		];
		$permission_ids = array();
		$permission_actions = [
			/*
			 * eaa: Edit all admins
			 * -----
			 * eua: Edit all users
			 * eta: Edit all themes
			 * epa: Edit all pages
			 * lpa: Lock all pages
			 * gpa: Open all pages
			 * eca: Edit all comments
			 * aca: Add comments to all pages
			 * vpa: View all pages
			 * -----
			 * euo: Edit own user
			 * eto: Edit own themes
			 * epo: Edit own pages
			 * lpo: Lock own pages
			 * gpo: Open own pages
			 * eco: Edit own comments
			 * aco: Add comments to own pages
			 * vpo: View own pages
			 * epg: Edit open pages
			 * acg: Add comments to open pages
			 * -----
			 * vpu: View unlocked pages
			 */
			['eaa', 'eua', 'eta', 'epa', 'lpa', 'gpa', 'eca', 'aca', 'vpa', 'euo', 'epo', 'lpo', 'gpo', 'eco', 'aco', 'vpo', 'epg', 'acg', 'vpu'],
			['eua', 'eta', 'epa', 'lpa', 'gpa', 'eca', 'aca', 'vpa', 'euo', 'epo', 'lpo', 'gpo', 'eco', 'aco', 'vpo', 'epg', 'acg', 'vpu'],
			['euo', 'epo', 'lpo', 'gpo', 'eco', 'aco', 'vpo', 'epg', 'acg', 'vpu'],
			['vpu'],
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

		echo("Creating actions\n");

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

		echo("Creating roles\n");

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