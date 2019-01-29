
import { query } from "./mysql";

const sprintf = require('sprintf-js').sprintf;
const crypto = require('crypto');

const SELECTOR_SIZE = 64;

export class AccessorError extends Error {
	constructor(...params) {
		super(...params);
	}
}

export class AccNotFoundError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class AccInitError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class AccInvalidSetError extends AccessorError {
	constructor(...params) {
		super(...params);
	}
}

export class Accessor {
	static get _classname() { return 'Accessor'; }
	static get _tablename() { return null; }
	static get _columns() { return null; }
	static get _identifiers() { return null; }
	static get _primary_identifer() { return null; }

	static async build(identity, identifier) {
		let object_body = await this.find(identity, identifier);
		return new this(object_body);
	}

	static async find_by(value, colname) {
		if (!this._columns.includes(colname)) thow(`Could not find column named '${colname}'`);
		return await query(`SELECT ${this._primary_identifer} FROM ${this._tablename} WHERE ${colname} = ?`, [value]);
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
		if (!result) throw new ReferenceError(`Cannot find a ${this._classname} with '${identifier}' = '${identity}'`);
		if (result.constructor === Array) throw new RangeError(`Receieved too many results when trying to determine identity of a ${this._classname} based on '${identifier}' = '${identity}'`);
		else return result;
	}

	static async is(identity, identifier) {
		try {
			let resp = await this.find(identity, identifier);
			return true
		} catch (err) {
			if (err instanceof AccNotFoundError)
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
		const sql = sprintf("DELETE FROM '%s' WHERE '%s' = ?", this._tablename, this._primary_identifer);
		return await query(sql, [this._primary_identifer]);
	}

	async get(colname) {
		if (!this._columns.includes(colname)) thow(`Could not find column named '${colname}'`);

		const sql = sprintf("SELECT %s FROM %s WHERE %s = ?", colname, this._tablename, this._primary_identifer);
		return await query(sql, [this.id]);
	}

	async set(colname, value) {
		if (!this._columns.includes(colname)) thow(`Could not find column named '${colname}'`);

		const sql = sprintf("UPDATE %s SET %s = ? WHERE %s = ?", this._tablename, colname, this._primary_identifer);
		return await query(sql, [value, this.id]);
	}

	constructor(object_body) {
		if (object_body == undefined) throw new AccInitError("Attempted to call accessor constructor without initializing objects");
		this._id = object_body[this._primary_identifer];
	}

	get id() { return this._id; }

	set id() { throw new AccInvalidSetError("Cannot access read-only 'id' property"); }

	async delete() {
		this.constructor.delete(this._id, this._primary_identifer);
	}
}