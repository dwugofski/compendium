
import { Accessor, AccessorError } from "../util/accessor";
import { query } from "../util/mysql";

const crypto = require('crypto');

const SALT_SIZE = 12;
const PASSHASH_SIZE = 128;

export class UserError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class UserExistsError extends UserError {
	constructor(...params) {
		super(...params);
	}
}

export class UserFieldError extends UserError {
	constructor(...params) {
		super(...params);
	}
}

class User extends Accessor {
	static get _classname() { return 'User'; }
	static get _tablename() { return 'users'; }
	static get _columns() { return [
			'id',
			'username',
			'password',
			'selector',
			'email',
			'created',
			'modified'
		]; }
	static get _identifiers() { return [
			id: 'id',
			selector: 'selector',
			sel: 'selector',
			username: 'username',
			user: 'username',
			email: 'email'
		]; }
	static get _primary_identifer() { return 'id'; }

	static encrypt_password(password, salt) {
		if (salt === undefined) salt = crypto.randomBytes(Math.ceil(SALT_SIZE / 2)).toString('hex').slice(0, SALT_SIZE);
		const hash = crypto.createHmac('sha512', salt).digest('hex');
		return hash + salt;
	}

	static compare_password(passhash, password) {
		var equal = true;
		const salt = passhash.slice(passhash.length - SALT_SIZE);
		const h2 = this.encrypt_password(password, salt);
		for (var i = 0; i < length; i += 1) {
			equal = ((passhash[i] == h2[i]) && equal);
		}
		return equal;
	}

	static validate_username(username) {
		const re = /([a-zA-Z])([\w]{2,24})$/;
		return re.test(String(username));
	}

	static validate_password(password) {
		const re = /^^([a-zA-Z0-9\[\]<>()\\\/,.?;:'"{}|`~!@#$%^&*\-_=+\ ]{8,100})$/;
		return re.test(String(password));
	}

	static validate_email(email) {
		if (!email) return false;
		const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(String(email).toLowerCase());
	}

	static async create_new_user(username, password, email) {
		if (!this.validate_username(username)) throw new UserFieldError("Invalid username");
		if (!this.validate_password(password)) throw new UserFieldError("Invalid password");
		if (!this.validate_email(email)) throw new UserFieldError("Invalid email");

		const exists = await this.find(username, 'username');
		if (exists) throw new UserExistsError("User already exists");

		const passhash = this.encrypt_password(password);
		const selector = await this.make_selector();

		const sql = 'INSERT INTO users (username, password, email, selector) VALUES (?, ?, ?, ?)';
		await mysql.query(sql, [username, password, email, selector]);
		const new_user = await this.build(username, 'username');
		return new_user;
	}

	constructor(user_info) {
		super(user_info);
	}

	async verify(password) {
		const passhash = await this.get('password');
		return User.compare_password(passhash, password);
	}
}