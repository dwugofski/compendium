
const mysql = require('../mysql');

const query = mysql.query;

async function tables(drop=false, create=true) {
	if (drop) {
		console.log("Dropping followings...");
		await query("DROP TABLE IF EXISTS followings CASCADE");
		console.log("Dropping user_roles...");
		await query("DROP TABLE IF EXISTS user_roles CASCADE");
		console.log("Dropping login_tokens...");
		await query("DROP TABLE IF EXISTS login_tokens CASCADE");
		console.log("Dropping users...");
		await query("DROP TABLE IF EXISTS users CASCADE");

		if (!create) return true;
	}

	try {
		console.log("Creating users...");
		await query("\
CREATE TABLE users (\
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, \
username VARCHAR(75) NOT NULL, \
password CHAR(140) NOT NULL, \
email VARCHAR(255) NULL DEFAULT NULL, \
selector VARCHAR(64) NOT NULL, \
created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, \
PRIMARY KEY (id), \
INDEX USER (username), \
UNIQUE INDEX SEL (selector))\
ENGINE = INNODB\
");

		console.log("Creating login_tokens...");
		await query("\
CREATE TABLE login_tokens (\
id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, \
selector VARCHAR(64) NOT NULL, \
valhash CHAR(140) NOT NULL, \
userid INT(11) UNSIGNED NOT NULL, \
expires DATETIME NOT NULL, \
PRIMARY KEY (id), \
INDEX sel (selector), \
INDEX user (userid), \
CONSTRAINT fk_user FOREIGN KEY (userid) \
REFERENCES users(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE) \
ENGINE = INNODB\
");
		
		console.log("Creating user_roles...");
		await query("\
CREATE TABLE user_roles (\
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, \
userid INT(10) UNSIGNED NOT NULL, \
perm_level VARCHAR(128) NOT NULL, \
PRIMARY KEY (id), \
CONSTRAINT fk_roles_user FOREIGN KEY (userid) \
REFERENCES users(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE)\
ENGINE = INNODB\
");

		console.log("Creating followings...");
		await query("\
CREATE TABLE followings (\
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, \
followed INT(10) UNSIGNED NOT NULL, \
follower INT(10) UNSIGNED NOT NULL, \
created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
PRIMARY KEY (id), \
FOREIGN KEY (followed) REFERENCES users(id) \
ON DELETE CASCADE ON UPDATE CASCADE, \
FOREIGN KEY (follower) REFERENCES users(id) \
ON DELETE CASCADE ON UPDATE CASCADE)\
ENGINE = INNODB\
");
	} catch(err) {
		console.log("Error trying to create user tables: " + err.message);
		throw err;
	}

}
exports.tables = tables;

function display_help() {
	console.log("\
+------------------------------------+\n\
|   Compendium User Table Creation   |\n\
+----------------------+-------------+\n\
                       | Usage Guide |\n\
                       +-------------+\n\
Usage:\n\
    Display usage guide:\n\
        node user_tables.js -h\n\
    Create new tables if they don't exist:\n\
        node user_tables.js\n\
    Create new tables (overwrite existing):\n\
        node user_tables.js -x\n\
    Delete existing tables:\n\
        node user_tables.js -xx\n\
");
}

if (require.main == module) {
	var help = false;
	var drop = false;
	var create = true;
	process.argv.forEach((val, index, array) => {
		if (val == "-h" || val == "--help") help = true;
		else if (val == "-xx" && !drop) create = false;
		else if (val == "-x" || val == "-xx") {
			drop = true;
			create = true;
		}
	});

	if (!help) {
		tables(drop, create).then(() => {
			console.log("Table creation successful!");
			process.exit();
		}).catch((err) => {
			console.log("Failed to create tables!");
			console.log(err);
			process.exit();
		});
	} else {
		display_help();
		process.exit();
	}
}