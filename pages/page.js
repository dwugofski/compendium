
const crypto = require('crypto');
const moment = require('moment');
const session = require('express-session');
const accessor = require('../util/accessor');
const mysql = require('../util/mysql');
const user = require('../users/user');

const Accessor = accessor.Accessor;
const AccessorError = accessor.AccessorError;
const query = mysql.query;
const User = user.User;
const UserNotFoundError = user.UserNotFoundError;

const READLIST_COLOR 	= 0;
const WRITELIST_COLOR 	= 1;

class PageError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageError = PageError;

class PageExistsError extends PageError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageError = PageError;

class PageNotFoundError extends PageError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageNotFoundError = PageNotFoundError;

class PageValueError extends PageError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageValueError = PageValueError;

class PageFieldError extends PageValueError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageFieldError = PageFieldError;

class PageReadOnlyError extends PageError {
	constructor(...params) {
		super(...params);
	}
}
exports.PageReadOnlyError = PageReadOnlyError;

class Page extends Accessor {
	static get _classname() { return 'Page'; }
	static get _tablename() { return 'pages'; }
	static get _columns() { return [
			'id',
			'selector',
			'title',
			'description',
			'author',
			'parent',
			'content',
			'opened',
			'locked',
			'created',
			'modified'
		]; }
	static get _identifiers() { return {
			id: 'id',
			selector: 'selector',
			sel: 'selector'
		}; }
	static get _primary_identifer() { return 'id'; }

	static validate_title(title) {
		if (!title) return false;
		if (title instanceof String) title = title.toString();
		if (typeof title !== 'string') return false;
		const re = /^([\w ]{1,64})$/;
		return re.test(title);
	}

	static validate_description(description) {
		if (description instanceof String) description = description.toString();
		if (typeof description !== 'string') return false;
		if (!description) return true;
		const re = /^([\w ]{1,128})$/;
		return re.test(description);
	}

	static validate_content(content) {
		if (!content) return false;
		if (content instanceof String) content = content.toString();
		if (typeof content !== 'string') return false;
		return true;
	}

	static async create(title, description, content, authorid, parent=null) {
		if (!this.validate_title(title)) throw new PageFieldError("Invalid title");
		if (!this.validate_description(description)) throw new PageFieldError("Invalid description");
		if (!this.validate_content(content)) throw new PageFieldError("Invalid content");

		if (!await User.is(authorid)) throw new PageFieldError("Invalid author");
		if ((parent !== null) && !await Page.is(parent)) throw new PageFieldError("Invalid parent");

		if ((parent !== null) && !(parent instanceof Page)) parent = await Page.build(parent);
		var pid = (parent === null) ? null : parent.id;

		const selector = await this.make_selector();

		const sql = 'INSERT INTO pages (title, description, content, author, parent, selector) VALUES (?, ?, ?, ?, ?, ?)';
		await mysql.query(sql, [title, description, content, authorid, pid, selector]);
		const new_page = await this.build(selector, 'selector');

		if (parent !== null) {
			let parent_accesslist = await query('SELECT \'user\', \'color\' FROM page_access WHERE page = ?', [pid]);
			if (parent_accesslist === null) parent_accesslist = [];
			if (!(parent_accesslist instanceof Array)) parent_accesslist = [parent_accesslist];
			parent_accesslist.forEach(async (row, index) => {
				await query('INSERT INTO page_access (page, user, color) VALUES (?, ?, ?)', [new_page.id, row.user, row.color]);
			});
			await new_page.accesslist(authorid);
		}

		return new_page;
	}

	constructor(page_info) {
		super(page_info);
	}

	async title(value = undefined) {
		if (value === undefined) {
			this.update();
			return this._title
		}
		else {
			if (value == this._title) return;
			if (Page.validate_title(value)) {
				await this.set('title', value);
				await this.update();
				return this;
			}
			else throw new PageValueError(`'${value}' is not a valid title`);
		}
	}

	async description(value = undefined) {
		if (value === undefined) {
			this.update();
			return this._description
		}
		else {
			if (value == this._description) return;
			if (Page.validate_description(value)) {
				await this.set('description', value);
				await this.update();
				return this;
			}
			else throw new PageValueError(`'${value}' is not a valid description`);
		}
	}

	async author(value = undefined) {
		if (value === undefined) {
			this.update();
			return await User.build(this._author);
		}
		else {
			if (value == this._author) return;
			if (User.is(value)) {
				if (value instanceof User) value = value.id;
				await this.set('author', value);
				await this.update();
				return this;
			}
			else throw new UserNotFoundError(`No user with id '${value}' found`);
		}
	}

	async parent(value = undefined) {
		if (value === undefined) {
			await this.update();
			if (this._parent == null) return null;
			else return await Page.build(this._parent);
		}
		else {
			if (value == this._parent) return;
			if (value === null || Page.is(value)) {
				if (value instanceof Page) value = value.id;
				await this.set('parent', value);
				await this.update();
				return this;
			}
			else throw new PageNotFoundError(`No page with id '${value}' found`);
		}
	}
	async has_parent() { return ((await this.parent()) !== null); }

	async content(value = undefined) {
		if (value === undefined) {
			this.update();
			return this._content;
		}
		else {
			if (value == this._content) return;
			if (Page.validate_content(value)) {
				if (value instanceof Page) value = value.id;
				await this.set('content', value);
				await this.update();
				return this;
			}
			else throw new PageValueError(`Invalid content`);
		}
	}

	async opened(value = undefined) {
		if (value === undefined) {
			this.update();
			return this._opened;
		}
		else {
			if (value == this._opened) return;
			if (value) await this.set('opened', true);
			else await this.set('opened', false);

			await this.update();
			return this;
		}
	}
	async close() { return this.opened(true); }
	async open() { return this.opened(false); }

	async locked(value = undefined) {
		if (value === undefined) {
			this.update();
			return this._locked;
		}
		else {
			if (value == this._locked) return;
			if (value) await this.set('locked', true);
			else await this.set('locked', false);

			await this.update();
			return this;
		}
	}
	async lock() { return this.locked(true); }
	async unlock() { return this.locked(false); }

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

	async accesslist(color=READLIST_COLOR, identity, identifier) {
		if (identity === undefined) {
			var usrs = [];
			if (color === null) usrs = await query(`SELECT user FROM page_access WHERE page = ?`, [this.id]);
			else usrs = await query(`SELECT user FROM page_access WHERE page = ? AND color = ?`, [this.id, color]);
			var ret = [];
			if (usrs == null) usrs = [];
			if (!(usrs instanceof Array)) usrs = [usrs];
			usrs.forEach((usr, index) => {
				ret.push(usr.user);
			});
			return ret;
		} else {
			if (color === null || color === undefined) color = READLIST_COLOR;
			if (!(await User.is(identity, identifier))) {
				throw new UserNotFoundError(`Could not find user with ${identifier} identifier set to ${identity}`);
			}
			const usr = await User.build(identity, identifier);
			const current_list = await this.accesslist(null);
			if (current_list.includes(usr.id)) {
				await query(`UPDATE page_access SET color = ? WHERE page = ? AND user = ?`, [color, this.id, wl_user.id]);
			}
			else {
				await query(`INSERT INTO page_access (page, user, color) VALUES (?, ?, ?)`, [this.id, wl_user.id, color]);
			}
			return this;
		}
	}
	async writelist(identity, identifier) { return accesslist(WRITELIST_COLOR, identity, identifier); }
	async readlist(identity, identifier) { return accesslist(READLIST_COLOR, identity, identifier); }

	async unlist(identity, identifier) {
		if (!(await User.is(identity, identifier))) {
			throw new UserNotFoundError(`Could not find user with ${identifier} identifier set to ${identity}`);
		}
		const usr = await User.build(identity, identifier);
		const current_list = await this.accesslist(null);
		if (!current_list.includes(usr.id)) {
			if (usr.id != this._author) {
				await query(`DROP FROM page_access WHERE page = ? AND user = ?`, [this.id, usr.id]);
			}
		}
		return this;
	}

	async listed(identity, identifier, color = null) {
		if (!(await User.is(identity, identifier))) {
			throw new UserNotFoundError(`Could not find user with ${identifier} identifier set to ${identity}`);
		}
		const usr = await User.build(identity, identifier);
		return (await this.accesslist(color).includes(usr.id));
	}
	async writelisted(identity, identifier) { return listed(identity, identifier, WRITELIST_COLOR); }
	async readlisted(identity, identifier) { return listed(identity, identifier, READLIST_COLOR); }

	async can_see(identity, identifier) {
		this.update();
		if (!(await User.is(identity, identifier))) {
			throw new UserNotFoundError(`Could not find user with ${identifier} identifier set to ${identity}`);
		}
		const usr = await User.build(identity, identifier);
		const curr_wl = await this.writelist();
		const curr_rl = await this.readlist();
		const actions = await user.actions();

		if (actions.includes(user.ACT_VIEW_ALL_PAGES)) return true;
		else if ((usr.id == this._author) && (actions.includes(user.ACT_VIEW_OWN_PAGES))) return true;
		else if ((curr_wl.includes(usr.id)) && (actions.includes(user.ACT_VIEW_UNLOCKED_PAGES))) return true;
		else if ((!this._locked) && (actions.includes(user.ACT_VIEW_UNLOCKED_PAGES))) return true;
		else if ((curr_rl.includes(usr.id)) && (actions.includes(user.ACT_VIEW_OPEN_PAGES))) return true;
		else if ((this._opened) && (actions.includes(user.ACT_VIEW_OPEN_PAGES))) return true;
		else return false;
	}

	async can_edit(identity, identifier) {
		this.update();
		if (!(await User.is(identity, identifier))) {
			throw new UserNotFoundError(`Could not find user with ${identifier} identifier set to ${identity}`);
		}
		const usr = await User.build(identity, identifier);
		const curr_wl = await this.writelist();
		const actions = await user.actions();

		if (actions.includes(user.ACT_EDIT_ALL_PAGES)) return true;
		else if ((usr.id == this._author) && (actions.includes(user.ACT_EDIT_OWN_PAGES))) return true;
		else if ((curr_wl.includes(usr.id)) && (actions.includes(user.ACT_EDIT_UNLOCKED_PAGES))) return true;
		else if ((!this._locked) && (actions.includes(user.ACT_EDIT_UNLOCKED_PAGES))) return true;
		else return false;
	}

	// async
	parents() {
		let parent_list = [];
		const iter_parent = page => {
			return page.has_parent().then(has_parent => {
				parent_list.push(page);
				if (has_parent) {
					return page.parent().then(iter_parent);
				} else return parent_list.splice(1);
			});
		}

		return iter_parent(this);
	}

	// async
	children() {
		return query(`SELECT id FROM pages WHERE parent = ?`, [this.id]).then(child_ids => {
			if (!child_ids) child_ids = [];
			if (!(child_ids instanceof Array)) child_ids = [child_ids];
			let child_list = [];
			let p_chain = new Promise( (resolve, reject) => resolve() );
			child_ids.forEach((row, i) => {
				let id = row['id'];
				p_chain = p_chain.then(() => {
					return Page.build(id, 'id');
				}).then((new_child) => {
					child_list.push(new_child);
				});
			});

			return p_chain.then(() => {return child_ids;});
		});
	}
}
exports.Page = Page;