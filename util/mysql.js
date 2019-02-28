
const mysql = require('mysql');

class MySQLError extends Error {
	constructor(...params) {
		super(...params);
	}
}
exports.MySQLError = MySQLError;

const conn = mysql.createConnection({
	host: "localhost",
	user: "compendium_admin",
	password: "compendium",
	database: "compendium"
});

conn.connect(function(err) {
	if (err) throw err;
});

async function execute_query(sql, args) {
	return new Promise((resolve, reject) => {
		if (args) {
			conn.query(sql, args, (err, response) => {
				if (err) reject(err);
				else resolve(response);
			});
		} else {
			conn.query(sql, args, (err, response) => {
				if (err) reject(err);
				else resolve(response);
			});
		}
	});
}

async function query(sql, args) {
	if (args && args.constructor !== Array) args = [args];
	if (!args) args = null;
	try {
		let resp = await execute_query(sql, args);
		
		if (resp && resp.length == 1) resp = resp[0];
		else if (resp && resp.length == 0) resp = null;
		return resp;
	} catch(err) {
		throw new MySQLError(err);
	}
}
exports.query = query;