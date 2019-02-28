
const mysql = require('../mysql');
const user = require('./user_tables');
const page = require('./page_tables');

async function create_tables(names, drop=false, create=true) {
	const chain = [
		{keys: ['user'], fn: user.tables},
		{keys: ['page'], fn: page.tables},
	];
	const do_all = names.includes('all');

	var promise_chain = new Promise((resolve, reject) => {resolve();});
	if (drop) {
		chain.slice().reverse().forEach(({keys, fn}, index, array) => {
			promise_chain = promise_chain.then(() => {
				let do_delete = do_all;
				if (!do_all) {
					for (let j = 0; j < keys.length; j++) {
						if (names.includes(keys[j])) {
							do_delete = true;
							break
						}
					}
				}

				if (do_delete) {
					console.log(`Deleting '${keys[0]}' database`);
					return fn(true, false);
				}
				else return;
			}).catch((err) => {throw err;});
		});
	}

	if (create) {
		chain.forEach(({keys, fn}, index, array) => {
			promise_chain = promise_chain.then(() => {
				let do_create = do_all;
				if (!do_all) {
					for (let j = 0; j < keys.length; j++) {
						if (names.includes(keys[j])) {
							do_create = true;
							break
						}
					}
				}

				if (do_create) {
					console.log(`Creating '${keys[0]}' database`);
					return fn(false, true);
				}
				else return;
			}).catch((err) => {
				throw err;
			});
		});
	}

	return promise_chain;
}

function display_help() {
	console.log("\
+------------------------------------+\n\
|   Compendium User Table Creation   |\n\
+----------------------+-------------+\n\
                       | Usage Guide |\n\
                       +-------------+\n\
Usage:\n\
    Display usage guide:\n\
        node compendium_tables.js -h\n\
    Create new tables if they don't exist:\n\
        node compendium_tables.js [<tables>...]\n\
    Create new tables (overwrite existing):\n\
        node compendium_tables.js [<tables>...] -x\n\
    Delete existing tables:\n\
        node compendium_tables.js [<tables>...] -xx\n\
Argumens:\n\
    tables:    Names of which tables to use. If none are supplied, 'all' will be\n\
               loaded\n\
\n\
Table Names:\n\
    all:     All tables (equivalent to running all the following)\n\
    user:    User tables: users, login_tokens, user_relationships\n\
");
}

if (require.main == module) {
	var help = false;
	var drop = false;
	var create = false;
	var names = [];
	process.argv.forEach((val, index, array) => {
		if (index < 2) return;
		if (val == "-h" || val == "--help") help = true;
		else if (val == "-xx" && !create) drop = true;
		else if (val == "-x" || val == "-xx") {
			drop = true;
			create = true;
		}
		else names.push(val);

	});
	
	if (!drop) create = true;
	if (names.length == 0) names.push('all');

	if (!help) {
		create_tables(names, drop, create).then(() => {
			console.log("Table creation successful!");
			process.exit();
		}).catch((err) => {
			console.log("Failed to create/delete tables!");
			console.log(err);
			process.exit();
		});
	} else {
		display_help();
		process.exit();
	}
}