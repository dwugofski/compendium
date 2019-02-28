
const mysql = require('../mysql');

const query = mysql.query;

async function tables(drop=false, create=true) {
	if (drop) {
		console.log("Dropping page_access...");
		await query("DROP TABLE IF EXISTS page_access CASCADE");
		console.log("Dropping pages...");
		await query("DROP TABLE IF EXISTS pages CASCADE");

		if (!create) return true;
	}

	try {
		console.log("Creating pages...");
		await query("\
CREATE TABLE pages (\
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, \
selector VARCHAR(64) NOT NULL, \
title VARCHAR(255) NOT NULL, \
description VARCHAR(255) DEFAULT NULL, \
author INT(10) UNSIGNED NOT NULL, \
parent INT(10) UNSIGNED DEFAULT NULL, \
content TEXT DEFAULT NULL, \
opened TINYINT UNSIGNED NOT NULL DEFAULT 0, \
locked TINYINT UNSIGNED NOT NULL DEFAULT 0, \
created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, \
PRIMARY KEY (id), \
UNIQUE INDEX SEL (selector), \
CONSTRAINT fk_page_author FOREIGN KEY (author) \
REFERENCES users(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE, \
CONSTRAINT fk_page_parent FOREIGN KEY (parent) \
REFERENCES pages(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE)\
ENGINE = INNODB\
");

		console.log("Creating page_access...");
		await query("\
CREATE TABLE page_access (\
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, \
page INT(10) UNSIGNED NOT NULL, \
user INT(10) UNSIGNED NOT NULL, \
created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, \
color TINYINT UNSIGNED NOT NULL, \
PRIMARY KEY (id), \
CONSTRAINT fk_accessing_user FOREIGN KEY (user) \
REFERENCES users(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE, \
CONSTRAINT fk_page_accessed FOREIGN KEY (page) \
REFERENCES pages(id) \
ON DELETE CASCADE \
ON UPDATE CASCADE \
) ENGINE = INNODB\
");
	} catch(err) {
		console.log("Error trying to create page tables: " + err.message);
		throw err;
	}

}
exports.tables = tables;

function display_help() {
	console.log("\
+------------------------------------+\n\
|   Compendium Page Table Creation   |\n\
+----------------------+-------------+\n\
                       | Usage Guide |\n\
                       +-------------+\n\
Usage:\n\
    Display usage guide:\n\
        node page_tables.js -h\n\
    Create new tables if they don't exist:\n\
        node page_tables.js\n\
    Create new tables (overwrite existing):\n\
        node page_tables.js -x\n\
    Delete existing tables:\n\
        node page_tables.js -xx\n\
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