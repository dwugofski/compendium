
const sprintf = require('sprintf-js').sprintf;
const crypto = require('crypto');
const query = require('./mysql').query;



const SELECTOR_SIZE = 64;
exports.SELECTOR_SIZE = SELECTOR_SIZE;

class AccessorError extends Error {
	constructor(...params) {
		super(...params);
	}
}
exports.AccessorError = AccessorError;

class AccNotFoundError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.AccNotFoundError = AccNotFoundError;

class AccInitError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.AccInitError = AccInitError;

class AccInvalidSetError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}
exports.AccInvalidSetError = AccInvalidSetError;

class Accessor extends Object{
	static get _classname() { return 'Accessor'; }
	static get _tablename() { return null; }
	static get _columns() { return null; }
	static get _identifiers() { return null; }
	static get _primary_identifer() { return null; }

	static async build(identity, identifier) {
		var object_body = await this.find(identity, identifier);
		var obj = new this(object_body);
		await obj.update();
		return obj;
	}

	static async find_by(value, colname) {
		if (!this._columns.includes(colname)) thow(`Could not find column named '${colname}'`);
		return query(`SELECT ${this._primary_identifer} FROM ${this._tablename} WHERE ${colname} = ?`, [value]);
	}

	static async find(identity, identifier) {
		if (identity instanceof this) {
			identity = identity.id;
			identifier = this._primary_identifer;
		}
		if (!identifier) identifier = this._primary_identifer;
		if (!this._identifiers.hasOwnProperty(identifier)) throw new AccessorError(`Could not find identifier column for '${identifier}'`);
		identifier = this._identifiers[identifier];
		if (!this._columns.includes(identifier)) throw new AccessorError(`Could not find column for identifier column '${identifier}'`);

		let result = await this.find_by(identity, identifier);
		if (!result) throw new AccNotFoundError(`Cannot find a ${this._classname} with '${identifier}' = '${identity}'`);
		if (result.constructor === Array) throw new AccessorError(`Receieved too many results when trying to determine identity of a ${this._classname} based on '${identifier}' = '${identity}'`);
		else return result;
	}

	static async is(identity, identifier) {
		try {
			let resp = await this.find(identity, identifier);
			return true;
		} catch (err) {
			if (err instanceof AccNotFoundError) return false;
			else throw(err);
		}
	}

	static async make_selector() {
		let selector = crypto.randomBytes(Math.ceil(SELECTOR_SIZE / 2)).toString('hex').slice(0, SELECTOR_SIZE);
		let i = 0;
		for ( i = 0; i < 10; i += 1) {
			let found = await this.is(selector, 'selector');
			if (found) selector = crypto.randomBytes(Math.ceil(SELECTOR_SIZE / 2)).toString('hex').slice(0, SELECTOR_SIZE);
			else break;
		}
		if (i == 10) throw new AccessorError(`Unable to find a selector in a reasonable number of tries`);
		return selector;
	}

	static async delete($identity, $identifier) {
		const object_body = await this.find($identity, $identifier);
		const sql = sprintf("DELETE FROM %s WHERE %s = ?", this._tablename, this._primary_identifer);
		return query(sql, [object_body.id]);
	}

	async get(colname) {
		if (!this.constructor._columns.includes(colname)) thow(`Could not find column named '${colname}'`);

		const sql = sprintf("SELECT %s FROM %s WHERE %s = ?", colname, this.constructor._tablename, this.constructor._primary_identifer);
		const ret = await query(sql, [this.id]);
		return ret[colname];
	}

	async set(colname, value) {
		if (!this.constructor._columns.includes(colname)) thow(`Could not find column named '${colname}'`);

		const sql = sprintf("UPDATE %s SET %s = ? WHERE %s = ?", this.constructor._tablename, colname, this.constructor._primary_identifer);
		return query(sql, [value, this.id]);
	}

	constructor(object_body) {
		super();
		if (object_body == undefined) throw new AccInitError("Attempted to call accessor constructor without initializing objects");
		this._id = object_body[this.constructor._primary_identifer];
	}

	get id() { return this._id; }

	set id(value) { throw new AccInvalidSetError("Cannot set read-only 'id' property"); }

	get props() {
		var obj = {};
		for (let i = 0; i < this.constructor._columns.length; i++) {
			let colname = this.constructor._columns[i];
			obj[colname] = this["_" + colname];
		}
		return obj;
	}

	async delete() {
		this.constructor.delete(this._id, this.constructor._primary_identifer);
	}

	async update() {
		for (let i = 0; i < this.constructor._columns.length; i++) {
			this['_'+this.constructor._columns[i]] = await this.get(this.constructor._columns[i]);
		}
	}
}
exports.Accessor = Accessor;