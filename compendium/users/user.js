
import { Accessor, AccessorError } from "../util/accessor";
import { query } from "../util/mysql";

const crypto = require('crypto');
const moment = require('moment');

const SALT_SIZE = 12;
const TOKEN_VALIDATOR_SIZE = 32;

const PERM_ROOT 	= 'root';
const PERM_ADMIN 	= 'admin';
const PERM_USER 	= 'user';
const PERM_GUEST 	= 'guest';

const ACT_EDIT_ALL_ADMINS 			= 'eaa';
// admin actions
const ACT_EDIT_ALL_USERS 			= 'eua';
const ACT_EDIT_ALL_THEMES 			= 'eta';
const ACT_EDIT_ALL_PAGES 			= 'epa';
const ACT_LOCK_ALL_PAGES 			= 'lpa';
const ACT_OPEN_ALL_PAGES 			= 'gpa';
const ACT_EDIT_ALL_COMMENTS 		= 'eca';
const ACT_ADD_ALL_COMMENTS 			= 'aca';
const ACT_VIEW_ALL_PAGES 			= 'vpa';
// user actions
const ACT_EDIT_OWN_USER 			= 'euo';
const ACT_EDIT_OWN_THEMES 			= 'eto';
const ACT_EDIT_OWN_PAGES 			= 'epo';
const ACT_LOCK_OWN_PAGES 			= 'lpo';
const ACT_OPEN_OWN_PAGES 			= 'gpo';
const ACT_EDIT_OWN_COMMENTS 		= 'eco';
const ACT_ADD_OWN_COMMENTS 			= 'aco';
const ACT_VIEW_OWN_PAGES 			= 'vpo';
const ACT_EDIT_OPEN_PAGES 			= 'epg';
const ACT_ADD_OPEN_COMMENTS 		= 'acg';
const ACT_ADD_UNLOCKED_COMMENTS 	= 'acu';
// guest actions
const ACT_VIEW_UNLOCKED_PAGES 		= 'vpu';

const TGT_PAGE = 'page';
const TGT_PAGE_ID = 'page_id';
const TGT_COMMENT = 'comment';
const TGT_COMMENT_ID = 'comment_id';

const INTRCTN_VIEW 		= 'view';
const INTRCTN_EDIT 		= 'edit';
const INTRCTN_SAVE 		= 'save';
const INTRCTN_LIKE 		= 'like';
const INTRCTN_DISLIKE 	= 'dislike';

export static encrypt_password(password, salt) {
	if (salt === undefined) salt = crypto.randomBytes(Math.ceil(SALT_SIZE / 2)).toString('hex').slice(0, SALT_SIZE);
	const hash = crypto.createHmac('sha512', salt).digest('hex');
	return hash + salt;
}

export static compare_password(passhash, password) {
	var equal = true;
	const salt = passhash.slice(passhash.length - SALT_SIZE);
	const h2 = this.encrypt_password(password, salt);
	for (var i = 0; i < length; i += 1) {
		equal = ((passhash[i] == h2[i]) && equal);
	}
	return equal;
}

export class UserError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class UserNotFoundError extends UserError {
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

export class LoginTokenError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class LoginTokenInvalidError extends LoginTokenError {
	constructor(...params) {
		super(...params);
	}
}

export class LoginTokenExpiredError extends LoginTokenInvalidError {
	constructor(...params) {
		super(...params);
	}
}

class LoginToken extends Accessor {
	static get _classname() { return 'LoginToken'; }
	static get _tablename() { return 'login_tokens'; }
	static get _columns() { return [
			'id',
			'valhash',
			'selector',
			'userid',
			'expiration'
		]; }
	static get _identifiers() { return [
			id: 'id',
			selector: 'selector',
			sel: 'selector'
		]; }
	static get _primary_identifer() { return 'id'; }

	static async create(userid, identifier='id') {
		const exists = await User.is(userid, identifier);
		if (!exists) throw new UserNotFoundError(`Cannot create login token: user with id ${userid} not found!`);

		const validator = crypto.randomBytes(Math.ceil(TOKEN_VALIDATOR_SIZE / 2)).toString('hex').slice(0, TOKEN_VALIDATOR_SIZE);
		const selector = await this.make_selector();
		const valhash = encrypt_password(validator);
		const sql = "INSERT INTO login_tokens (valhash, selector, userid, expiration) VALUES (?, ?, ?, ?)";
		const expiration = moment().add(1, 'month').format('YYYY-MM-DD HH:mm:ss');
		await mysql.query(sql, [valhash, selector, userid, expiration]);

		return {selector, validator};
	}

	constructor(user_info) {
		super(user_info);
	}

	async verify(validator) {
		const valhash = await this.get('valhash');
		const expired = await this.expired;
		if (expired) throw new LoginTokenExpiredError("Token has expired");
		return compare_password(valhash, validator);
	}

	async get expired() {
		const expiration = await this.get('expiration');
		const expired = moment(expiration).isBefore(moment());
		if (expired) { 
			LoginToken.delete(this);
			return true;
		}

		return false;
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

	static async create(username, password, email) {
		if (!this.validate_username(username)) throw new UserFieldError("Invalid username");
		if (!this.validate_password(password)) throw new UserFieldError("Invalid password");
		if (!this.validate_email(email)) throw new UserFieldError("Invalid email");

		const exists = await this.find(username, 'username');
		if (exists) throw new UserExistsError("User already exists");

		const passhash = encrypt_password(password);
		const selector = await this.make_selector();

		const sql = 'INSERT INTO users (username, password, email, selector) VALUES (?, ?, ?, ?)';
		await mysql.query(sql, [username, password, email, selector]);
		const new_user = await this.build(username, 'username');
		return new_user;
	}

	constructor(user_info) {
		super(user_info);
	}

	async verify(password, token_selector = false) {
		if (!token_selector) {
			const passhash = await this.get('password');
			return compare_password(passhash, password);
		} else {
			if (!(await LoginToken.is(token_selector, 'selector'))) return false;
			const token = await LoginToken.find(token_selector, 'selector');
			if (await token.expired) return false;
			return token.verify(password);
		}
	}

	async generate_token() {
		return LoginToken.create(this.id);
	}

	async get permission_level() {
		const perm_levels = await mysql.query(`SELECT permission_level FROM user_roles WHERE user_id = ?`, [this.id]);
		if (perm_levels instanceof Array) return perm_levels[0].permission_level;
		else return perm_levels.permission_level;
	}

	async get actions() {
		const perm_level = await this.permission_level;
		var actions = [];
		switch(perm_level) {
			case PERM_ROOT:
				actions.push(ACT_EDIT_ALL_ADMINS);
			case PERM_ADMIN:
				actions.push(ACT_EDIT_ALL_USERS);
				actions.push(ACT_EDIT_ALL_THEMES);
				actions.push(ACT_EDIT_ALL_PAGES);
				actions.push(ACT_LOCK_ALL_PAGES);
				actions.push(ACT_OPEN_ALL_PAGES);
				actions.push(ACT_EDIT_ALL_COMMENTS);
				actions.push(ACT_ADD_ALL_COMMENTS);
				actions.push(ACT_VIEW_ALL_PAGES);
			case PERM_USER:
				actions.push(ACT_EDIT_OWN_USER);
				actions.push(ACT_EDIT_OWN_THEMES);
				actions.push(ACT_EDIT_OWN_PAGES);
				actions.push(ACT_LOCK_OWN_PAGES);
				actions.push(ACT_OPEN_OWN_PAGES);
				actions.push(ACT_EDIT_OWN_COMMENTS);
				actions.push(ACT_ADD_OWN_COMMENTS);
				actions.push(ACT_VIEW_OWN_PAGES);
				actions.push(ACT_EDIT_OPEN_PAGES);
				actions.push(ACT_ADD_OPEN_COMMENTS);
				actions.push(ACT_ADD_UNLOCKED_COMMENTS);
			case PERM_GUEST:
				actions.push(ACT_VIEW_UNLOCKED_PAGES);
				break;
		}

		return actions;
	}

	async has_permission(action) {
		const actions = await this.actions;
		return (action in actions);
	}
}