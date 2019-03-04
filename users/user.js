
const crypto = require('crypto');
const moment = require('moment');
const session = require('express-session');
const accessor = require('../util/accessor');
const mysql = require('../util/mysql');

const Accessor = accessor.Accessor;
const AccessorError = accessor.AccessorError;
const query = mysql.query;



const SALT_SIZE = 12; 				exports.SALT_SIZE = SALT_SIZE;
const TOKEN_VALIDATOR_SIZE = 32; 	exports.TOKEN_VALIDATOR_SIZE = TOKEN_VALIDATOR_SIZE;

const PERM_ROOT 	= 'root'; 	exports.PERM_ROOT = PERM_ROOT;
const PERM_ADMIN 	= 'admin'; 	exports.PERM_ADMIN = PERM_ADMIN;
const PERM_USER 	= 'user'; 	exports.PERM_USER = PERM_USER;
const PERM_GUEST 	= 'guest'; 	exports.PERM_GUEST = PERM_GUEST;

const ACT_EDIT_ALL_ADMINS 			= 'eaa'; 	exports.ACT_EDIT_ALL_ADMINS = ACT_EDIT_ALL_ADMINS;
// admin actions
const ACT_EDIT_ALL_USERS 			= 'eua'; 	exports.ACT_EDIT_ALL_USERS = ACT_EDIT_ALL_USERS;
const ACT_EDIT_ALL_THEMES 			= 'eta'; 	exports.ACT_EDIT_ALL_THEMES = ACT_EDIT_ALL_THEMES;
const ACT_DELETE_ALL_PAGES 			= 'dpa'; 	exports.ACT_DELETE_ALL_PAGES = ACT_DELETE_ALL_PAGES;
const ACT_EDIT_ALL_PAGES 			= 'epa'; 	exports.ACT_EDIT_ALL_PAGES = ACT_EDIT_ALL_PAGES;
const ACT_LOCK_ALL_PAGES 			= 'lpa'; 	exports.ACT_LOCK_ALL_PAGES = ACT_LOCK_ALL_PAGES;
const ACT_OPEN_ALL_PAGES 			= 'gpa'; 	exports.ACT_OPEN_ALL_PAGES = ACT_OPEN_ALL_PAGES;
const ACT_EDIT_ALL_COMMENTS 		= 'eca'; 	exports.ACT_EDIT_ALL_COMMENTS = ACT_EDIT_ALL_COMMENTS;
const ACT_ADD_ALL_COMMENTS 			= 'aca'; 	exports.ACT_ADD_ALL_COMMENTS = ACT_ADD_ALL_COMMENTS;
const ACT_VIEW_ALL_PAGES 			= 'vpa'; 	exports.ACT_VIEW_ALL_PAGES = ACT_VIEW_ALL_PAGES;
// user actions
const ACT_EDIT_OWN_USER 			= 'euo'; 	exports.ACT_EDIT_OWN_USER = ACT_EDIT_OWN_USER;
const ACT_EDIT_OWN_THEMES 			= 'eto'; 	exports.ACT_EDIT_OWN_THEMES = ACT_EDIT_OWN_THEMES;
const ACT_CREATE_OWN_PAGES 			= 'cpo'; 	exports.ACT_CREATE_OWN_PAGES = ACT_CREATE_OWN_PAGES;
const ACT_DELETE_OWN_PAGES 			= 'dpo'; 	exports.ACT_DELETE_OWN_PAGES = ACT_DELETE_OWN_PAGES;
const ACT_EDIT_OWN_PAGES 			= 'epo'; 	exports.ACT_EDIT_OWN_PAGES = ACT_EDIT_OWN_PAGES;
const ACT_LOCK_OWN_PAGES 			= 'lpo'; 	exports.ACT_LOCK_OWN_PAGES = ACT_LOCK_OWN_PAGES;
const ACT_OPEN_OWN_PAGES 			= 'gpo'; 	exports.ACT_OPEN_OWN_PAGES = ACT_OPEN_OWN_PAGES;
const ACT_EDIT_OWN_COMMENTS 		= 'eco'; 	exports.ACT_EDIT_OWN_COMMENTS = ACT_EDIT_OWN_COMMENTS;
const ACT_ADD_OWN_COMMENTS 			= 'aco'; 	exports.ACT_ADD_OWN_COMMENTS = ACT_ADD_OWN_COMMENTS;
const ACT_VIEW_OWN_PAGES 			= 'vpo'; 	exports.ACT_VIEW_OWN_PAGES = ACT_VIEW_OWN_PAGES;
const ACT_EDIT_UNLOCKED_PAGES		= 'epu'; 	exports.ACT_EDIT_UNLOCKED_PAGES = ACT_EDIT_UNLOCKED_PAGES;
const ACT_ADD_UNLOCKED_COMMENTS 	= 'acu'; 	exports.ACT_ADD_UNLOCKED_COMMENTS = ACT_ADD_UNLOCKED_COMMENTS;
const ACT_ADD_OPEN_COMMENTS 		= 'acg'; 	exports.ACT_ADD_OPEN_COMMENTS = ACT_ADD_OPEN_COMMENTS;
// guest actions
const ACT_VIEW_OPEN_PAGES 			= 'vpg'; 	exports.ACT_VIEW_OPEN_PAGES = ACT_VIEW_OPEN_PAGES;

const TGT_PAGE 			= 'page'; 			exports.TGT_PAGE = TGT_PAGE;
const TGT_PAGE_ID 		= 'page_id'; 		exports.TGT_PAGE_ID = TGT_PAGE_ID;
const TGT_COMMENT 		= 'comment'; 		exports.TGT_COMMENT = TGT_COMMENT;
const TGT_COMMENT_ID 	= 'comment_id'; 	exports.TGT_COMMENT_ID = TGT_COMMENT_ID;

const INTRCTN_VIEW 		= 'view'; 		exports.INTRCTN_VIEW = INTRCTN_VIEW;
const INTRCTN_EDIT 		= 'edit'; 		exports.INTRCTN_EDIT = INTRCTN_EDIT;
const INTRCTN_SAVE 		= 'save'; 		exports.INTRCTN_SAVE = INTRCTN_SAVE;
const INTRCTN_LIKE 		= 'like'; 		exports.INTRCTN_LIKE = INTRCTN_LIKE;
const INTRCTN_DISLIKE 	= 'dislike'; 	exports.INTRCTN_DISLIKE = INTRCTN_DISLIKE;

function encrypt_password(password, salt) {
	if (salt === undefined) salt = crypto.randomBytes(Math.ceil(SALT_SIZE / 2)).toString('hex').slice(0, SALT_SIZE);
	const hash = crypto.createHmac('sha512', salt).update(password).digest('hex');
	return hash + salt;
}
exports.encrypt_password = encrypt_password;

function compare_password(passhash, password) {
	var equal = true;
	const salt = passhash.slice(passhash.length - SALT_SIZE);
	const secondhash = encrypt_password(password, salt);
	const buff1 = new Buffer(passhash);
	const buff2 = new Buffer(secondhash);
	return crypto.timingSafeEqual(buff1, buff2);
}
exports.compare_password = compare_password;

class UserError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserError = UserError;

class UserNotFoundError extends UserError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserNotFoundError = UserNotFoundError;

class UserExistsError extends UserError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserExistsError = UserExistsError;

class UserInvalidValueError extends UserError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserInvalidValueError = UserInvalidValueError;

class UserFieldError extends UserInvalidValueError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserFieldError = UserFieldError;

class UserPermissionLevelError extends UserInvalidValueError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserPermissionLevelError = UserPermissionLevelError;

class UserReadOnlyError extends UserError {
	constructor(...params) {
		super(...params);
	}
}
exports.UserReadOnlyError = UserReadOnlyError;

class LoginTokenError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.LoginTokenError = LoginTokenError;

class LoginTokenInvalidFieldError extends LoginTokenError {
	constructor(...params) {
		super(...params);
	}
}
exports.LoginTokenInvalidFieldError = LoginTokenInvalidFieldError;

class LoginTokenInvalidError extends LoginTokenError {
	constructor(...params) {
		super(...params);
	}
}
exports.LoginTokenInvalidError = LoginTokenInvalidError;

class LoginTokenExpiredError extends LoginTokenInvalidError {
	constructor(...params) {
		super(...params);
	}
}
exports.LoginTokenExpiredError = LoginTokenExpiredError;

class LoginTokenReadOnlyError extends LoginTokenInvalidError {
	constructor(...params) {
		super(...params);
	}
}
exports.LoginTokenReadOnlyError = LoginTokenReadOnlyError;

class LoginToken extends Accessor {
	static get _classname() { return 'LoginToken'; }
	static get _tablename() { return 'login_tokens'; }
	static get _columns() { return [
			'id',
			'valhash',
			'selector',
			'userid',
			'expires'
		]; }
	static get _identifiers() { return {
			id: 'id',
			selector: 'selector',
			sel: 'selector'
		}; }
	static get _primary_identifer() { return 'id'; }

	static async create(userid, identifier='id', duration) {
		const exists = await User.is(userid, identifier);
		if (!exists) throw new UserNotFoundError(`Cannot create login token: user with id ${userid} not found!`);

		if (duration === undefined || duration === null) duration = moment.duration(1, 'month');
		else if (!moment.isDuration(duration)) {
			if (!('units' in duration) || !('number' in duration)) throw new LoginTokenInvalidFieldError(`Cannot parse ${JSON.stringify(duration)} as duration!`);
			else duration = moment.duration(duration.number, duration.units);
		}

		const validator = crypto.randomBytes(Math.ceil(TOKEN_VALIDATOR_SIZE / 2)).toString('hex').slice(0, TOKEN_VALIDATOR_SIZE);
		const selector = await this.make_selector();
		const valhash = encrypt_password(validator);
		const sql = "INSERT INTO login_tokens (valhash, selector, userid, expires) VALUES (?, ?, ?, ?)";
		const expiration = moment().add(duration).format('YYYY-MM-DD HH:mm:ss');
		await mysql.query(sql, [valhash, selector, userid, expiration]);

		return {selector, validator};
	}

	constructor(user_info) {
		super(user_info);
	}

	async verify(validator) {
		const valhash = await this.get('valhash');
		const expired = await this.expired();
		if (expired) throw new LoginTokenExpiredError("Token has expired");
		return compare_password(valhash, validator);
	}

	async expired(value) {
		if (value === undefined) {
			const expiration = await this.get('expires');
			const expired = moment(expiration).isBefore(moment());
			if (expired) {
				await LoginToken.delete(this);
				return true;
			}
			return false;
		} else throw new LoginTokenReadOnlyError("Cannot set read-opnly 'expired' property of LoginToken");
	}

	async userid(value) {
		if (value === undefined) {
			this._userid = await this.get('userid');
			return this._userid;
		} else throw new LoginTokenReadOnlyError("Cannot set read-opnly 'userid' property of LoginToken");
	}
}
exports.LoginToken = LoginToken;

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
	static get _identifiers() { return {
			id: 'id',
			selector: 'selector',
			sel: 'selector',
			username: 'username',
			user: 'username',
			email: 'email'
		}; }
	static get _primary_identifer() { return 'id'; }

	static get permission_levels() { return [
		PERM_ROOT,
		PERM_ADMIN,
		PERM_USER,
		PERM_GUEST,
	]; }
	static get all_actions() { return [
		ACT_EDIT_ALL_ADMINS,
		ACT_EDIT_ALL_USERS,
		ACT_EDIT_ALL_THEMES,
		ACT_DELETE_ALL_PAGES,
		ACT_EDIT_ALL_PAGES,
		ACT_LOCK_ALL_PAGES,
		ACT_OPEN_ALL_PAGES,
		ACT_EDIT_ALL_COMMENTS,
		ACT_ADD_ALL_COMMENTS,
		ACT_VIEW_ALL_PAGES,
		ACT_EDIT_OWN_USER,
		ACT_EDIT_OWN_THEMES,
		ACT_CREATE_OWN_PAGES,
		ACT_DELETE_OWN_PAGES,
		ACT_EDIT_OWN_PAGES,
		ACT_LOCK_OWN_PAGES,
		ACT_OPEN_OWN_PAGES,
		ACT_EDIT_OWN_COMMENTS,
		ACT_ADD_OWN_COMMENTS,
		ACT_VIEW_OWN_PAGES,
		ACT_EDIT_UNLOCKED_PAGES,
		ACT_ADD_UNLOCKED_COMMENTS,
		ACT_ADD_UNLOCKED_COMMENTS,
		ACT_VIEW_OPEN_PAGES
	]; }

	static validate_username(username) {
		if (!username) return false;
		if (username instanceof String) username = username.toString();
		if (typeof username !== 'string') return false;
		const re = /^([a-zA-Z])([\w]{2,24})$/;
		return re.test(username);
	}

	static validate_password(password) {
		if (!password) return false;
		if (password instanceof String) password = password.toString();
		if (typeof password !== 'string') return false;
		const re = /^([a-zA-Z0-9\[\]<>()\\\/,.?;:'"{}|`~!@#$%^&*\-_=+\ ]{8,100})$/;
		return re.test(password);
	}

	static validate_email(email) {
		if (!email) return false;
		if (email instanceof String) email = email.toString();
		if (typeof email !== 'string') return false;
		const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(email.toLowerCase());
	}

	static async create(username, password, email) {
		email = email.toLowerCase(); // Store in lower case for consistency
		if (!this.validate_username(username)) throw new UserFieldError("Invalid username");
		if (!this.validate_password(password)) throw new UserFieldError("Invalid password");
		if (!this.validate_email(email)) throw new UserFieldError("Invalid email");

		var exists = await this.is(username, 'username');
		if (exists) throw new UserExistsError("User already exists");

		exists = await this.is(email, 'email');
		if (exists) throw new UserExistsError("User already exists");

		const passhash = encrypt_password(password);
		const selector = await this.make_selector();

		const sql = 'INSERT INTO users (username, password, email, selector) VALUES (?, ?, ?, ?)';
		await mysql.query(sql, [username, passhash, email, selector]);
		const new_user = await this.build(username, 'username');
		await mysql.query('INSERT INTO user_roles (userid, perm_level) VALUES (?, ?)', [new_user.id, PERM_GUEST]);
		return new_user;
	}

	constructor(user_info) {
		super(user_info);
	}

	async username(value = undefined) {
		if (value === undefined) {
			await this.update();
			return this._username;
		}
		else {
			if (value == this._username) return;
			if (User.validate_username(value)) {
				let exists = await User.is(value, 'username');
				if (!exists) {
					await this.set('username', value);
					await this.update();
					return this;
				}
				else throw new UserExistsError(`User with username '${value}' already exists`);
			}
			else throw new UserInvalidValueError(`'${value}' is not a valid username`);
		}
	}

	async selector(value = undefined) {
		if (value === undefined) {
			await this.update();
			return this._selector;
		}
		else throw new UserReadOnlyError("Cannot set read-only property 'selector' of User");
	}

	async password(value = undefined) {
		if (value === undefined) {
			await this.update();
			return this._password;
		}
		else {
			if (!User.validate_password(value)) throw new UserInvalidValueError(`Invalid password value`);
			const passhash = encrypt_password(value);
			await this.set('password', passhash);
			await this.update();
			return this;
		}
	}

	async email(value = undefined) {
		if (value === undefined) {
			await this.update();
			return this._email;
		}
		else {
			if (User.validate_email(value)) {
				const email_used = await User.is(value, 'email');
				if (!email_used) {
					await this.set('email', value);
					await this.update();
					return this;
				}
				else throw new UserExistsError('Email already in use');
			} else throw new UserInvalidValueError(`'${value}' is not a valid email`);
		}
	}

	async created(value = undefined) {
		if (value === undefined) {
			await this.update();
			return moment(this._created);
		} else throw new UserReadOnlyError("Cannot set read-only property 'created' of User");
	}

	async modified(value = undefined) {
		if (value === undefined) {
			await this.update();
			return moment(this._modified);
		} else throw new UserReadOnlyError("Cannot set read-only property 'modified' of User");
	}

	async verify(password, is_token = false) {
		if (!is_token) {
			const passhash = await this.password();
			return compare_password(passhash, password);
		} else {
			if (!(await LoginToken.is(password.selector, 'selector'))) return false;
			const token = await LoginToken.build(password.selector, 'selector');
			if (token.props.userid != this.id) return false;
			const expired = await token.expired();
			if (expired) return false;
			return token.verify(password.validator);
		}
	}

	async generate_token(token_duration) {
		return LoginToken.create(this.id, 'id', token_duration);
	}

	async permission_level(value = undefined) {
		if (value === undefined) {
			const perm_levels = await mysql.query(`SELECT perm_level FROM user_roles WHERE userid = ?`, [this.id]);
			if (perm_levels instanceof Array) {
				throw new UserInvalidValueError(`Found multiple permission levels for '${this._username}'`);
			}
			else if (!User.permission_levels.includes(perm_levels.perm_level)) {
				throw new UserPermissionLevelError(`Permission level '${perm_levels.perm_level}' not recognized!`);
			}
			else return perm_levels.perm_level;
		} else {
			if (!User.permission_levels.includes(value)) {
				throw new UserPermissionLevelError(`Permission level '${value}' not recognized!`);
			}
			else return mysql.query(`UPDATE user_roles SET perm_level = ? WHERE userid = ?`, [value, this.id]);
		}
	}

	async actions(value = undefined) {
		if (value === undefined) {
			const perm_level = await this.permission_level();
			var actions = [];
			switch(perm_level) {
				case PERM_ROOT:
					actions.push(ACT_EDIT_ALL_ADMINS);
				case PERM_ADMIN:
					actions.push(ACT_EDIT_ALL_USERS);
					actions.push(ACT_EDIT_ALL_THEMES);
					actions.push(ACT_DELETE_ALL_PAGES);
					actions.push(ACT_EDIT_ALL_PAGES);
					actions.push(ACT_LOCK_ALL_PAGES);
					actions.push(ACT_OPEN_ALL_PAGES);
					actions.push(ACT_EDIT_ALL_COMMENTS);
					actions.push(ACT_ADD_ALL_COMMENTS);
					actions.push(ACT_VIEW_ALL_PAGES);
				case PERM_USER:
					actions.push(ACT_EDIT_OWN_USER);
					actions.push(ACT_EDIT_OWN_THEMES);
					actions.push(ACT_CREATE_OWN_PAGES);
					actions.push(ACT_DELETE_OWN_PAGES);
					actions.push(ACT_EDIT_OWN_PAGES);
					actions.push(ACT_LOCK_OWN_PAGES);
					actions.push(ACT_OPEN_OWN_PAGES);
					actions.push(ACT_EDIT_OWN_COMMENTS);
					actions.push(ACT_ADD_OWN_COMMENTS);
					actions.push(ACT_VIEW_OWN_PAGES);
					actions.push(ACT_EDIT_UNLOCKED_PAGES);
					actions.push(ACT_ADD_UNLOCKED_COMMENTS);
					actions.push(ACT_ADD_OPEN_COMMENTS);
				case PERM_GUEST:
					actions.push(ACT_VIEW_OPEN_PAGES);
					break;
			}

			return actions;
		} else throw new UserReadOnlyError("Cannot set read-only property 'actions' of User");
	}

	async has_permission(action) {
		const actions = await this.actions;
		return (action in actions);
	}

	async follower_list(value = undefined) {
		if (value === undefined) {
			const rows = await query(`SELECT follower FROM followings WHERE followed = ?`, [this.id]);
			var followers = [];
			if (!rows) return followers;
			if (rows.constructor !== Array) rows = [rows];
			for (var i = 0; i < rows.length; i++) {
				var row_user = await User.build(rows[i].follower);
				followers.push(row_user);
			}
			return followers;
		} else throw new UserReadOnlyError("Cannot set read-only property 'follower_list' of User");
	}

	async followed_list() {
		if (value === undefined) {
			const rows = await query(`SELECT followed FROM followings WHERE follower = ?`, [this.id]);
			var follows = [];
			if (!rows) return follows;
			if (rows.constructor !== Array) rows = [rows];
			for (var i = 0; i < rows.length; i++) {
				var row_user = await User.build(rows[i].followed);
				follows.push(row_user);
			}
			return follows;
		} else throw new UserReadOnlyError("Cannot set read-only property 'followed_list' of User");
	}

	async follow(identity, identifier = User._primary_identifer) {
		const followed = await User.find(identity, identifier);
		const search_results = await query('SELECT id FROM followings WHERE follower = ? AND followed = ?', [this.id, followed.id]);
		if (search_results) return;
		return await query('INSERT INTO followings (follower, followed) VALUES (?, ?)', [this.id, followed.id]);
	}

	async unfollow(identity, identifier = User._primary_identifer) {
		const followed = await User.find(identity, identifier);
		const search_results = await query('SELECT id FROM followings WHERE follower = ? AND followed = ?', [this.id, followed.id]);
		if (!search_results) return;
		if (search_results.constructor === Array) throw new UserError(`Duplicate follower/followed entries in followings table with follower = ${this.id} and followed = ${followed.id}`);
		return await query('DELETE FROM followings WHERE follower = ? AND followed = ?', [this.id, followed.id]);
	}

	async follows(identity, identifier = User._primary_identifer) {
		const followed = await User.find(identity, identifier);
		const followeds = await this.followeds;
		var found = false;
		var i;
		for ( i = 0; i < followeds.length; i += 1 ) {
			if (followeds[i].id == followed.id) {
				found = true;
				break;
			}
		}

		return found;
	}

	async is_follower(identity, identifier = User._primary_identifer) {
		const follower = await User.find(identity, identifier);
		return follower.follows(this);
	}
}
exports.User = User;

exports.signup = (req, res, next) => {
	const username = req.body.username;
	const email = req.body.email;
	const password = req.body.password1;

	console.log("Trying signup");

	if (!User.validate_username(username)) throw new UserFieldError('Invalid username!');
	else if (!User.validate_email(email)) throw new UserFieldError('Invalid email!');
	else if (!User.validate_password(password)) throw new UserFieldError('Invalid password!');
	else {
		User.is(username, 'user').then((user_exists) => {
			if (user_exists) throw new UserExistsError('Username already in use!');
			else return User.is(email, 'email');
		}).then((email_exists) => {
			if (email_exists) throw new UserExistsError('Email already in use!');
			else return User.create(username, password, email);
		}).then((new_user) => {
			return new_user.username();
		}).then((username) => {
			console.log(`New user: '${username}'' created!`);
			res.status(201).location('/user/'+username).send(`Successfully created user '${username}'`);
		}).catch((err) => {
			if (err instanceof UserExistsError) res.status(409).send(err.message);
			else next(err);
		});
	}
};

exports.login = (req, res, next) => {
	const identity = req.body.user;
	const password = req.body.password;
	var identifier = 'username';

	if ( !User.validate_username(identity) && !User.validate_email(identity) ) throw new UserFieldError('Invalid username / email');
	else if ( !User.validate_password(password) ) throw new UserFieldError('Invalid password');
	else {
		if ( !User.validate_username(identity) ) identifier = 'email';

		User.is(identity, identifier).then((user_found) => {
			if (!user_found) {
				if (identifier == 'email') throw new UserNotFoundError('User email not found');
				else if ( !User.validate_email(identity) ) throw new UserNotFoundError('Username not found');
				else {
					return new Promise((resolve, reject) => {
						User.find(identity, 'email').then((user_found) => {
							if (user_found) User.build(identity, 'email').then(resolve).catch(reject);
						}).catch(reject);
					});
				}
			} else return User.build(identity, identifier);
		}).then((user) => {
			req.session.user = user;
			return user.generate_token();
		}).then((token) => {
			console.log(token);
			let user = req.session.user;
			req.session.user_token = token;
			let response = {
				message: `Successfully logged on as ${user.props.username}`,
				token: token
			};
			res.status(200).location('/user/'+user.props.username).send(JSON.stringify(response));
		}).catch((err) => {
			console.log(`Caught`);
			console.log(err);
			if (err instanceof UserNotFoundError) res.status(404).send(err.message);
			else if (err instanceof UserFieldError) res.status(400).send(err.message);
			else next(err);
		});
	}
};