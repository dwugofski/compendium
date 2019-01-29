
const mysql = require('mysql');

export class MySQLError extends Error {
	constructor(...params) {
		super(...params);
	}
}

const conn = mysql.createConnection({
	host: "localhost",
	user: "yourusername",
	password: "yourpassword"
});

conn.connect(function(err) {
	if (err) throw error;
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

export async function query(sql, args) {
	if (args && args.constructor !== Array) args = [args];
	if (!args) args = null;
	try {
		let resp = await execute_query(sql, args);

		if (resp && resp.length == 1) resp = resp[0];
		return resp;
	} catch(err) {
		throw new MySQLError(err);
	}
}