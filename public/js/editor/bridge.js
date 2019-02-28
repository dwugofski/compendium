
import { Classable } from "../jclasses.js"

class EditorBridge extends Classable(Object) {
	constructor(...args) {
		super(...args);
		this._create_binding("mark_change");
		this._create_binding("block_change");
		this._create_binding("add_img");
		this.marks = {};
		this.blocks = {};
	}

	_track_generic(type, base) {
		if (typeof type == 'string' && type.length > 0 && this.has_class_toggle(type)) return;

		this.add_class_toggle(type);

		Object.defineProperty(this[base+"s"], [type], {
			configurable: true,
			get: () => { return this[type]; },
			set: (value) => { this[type] = value; }
		});

		this['bind_make_'+type]((() => { this["on_"+base+"_change"](type); }).bind(this));
		this['bind_un_'+type]((() => { this["on_"+base+"_change"](type); }).bind(this));
	}

	_untrack_generic(type, base) {
		if (typeof type == 'string' && type.length > 0 && !this.has_class_toggle(type)) return;

		delete this[base+"s"][type];
		this.remove_class_toggle(type);
	}

	track_mark(type) { this._track_generic(type, "mark") }
	untrack_mark(type) { this._untrack_generic(type, "mark") }
	track_block(type) { this._track_generic(type, "block") }
	untrack_block(type) { this._untrack_generic(type, "block") }

	tracking_block(type) { return this.blocks[type] !== undefined; }
	has_block(type) { return (this.tracking_block(type)) ? this.blocks[type] : false; }
	get active_blocks() {
		var block_list = [];
		const prop_list = Object.getOwnPropertyNames(this.blocks);
		for(var i in prop_list) {
			const block = prop_list[i];
			if (this.blocks[block]) block_list.push(block);
		}
		return block_list;
	}
	get active_block() {
		return this.active_blocks[0];
	}
	set active_block(new_type) {
		if (!this.tracking_block(new_type)) return;
		if (this.has_block(new_type)) return;

		this.blocks[this.active_block] = false;
		this.blocks[new_type] = true;
	}

	tracking_mark(type) { return this.marks[type] !== undefined; }
	has_mark(type) { return (this.tracking_mark(type)) ? this.marks[type] : false; }
	get active_marks() {
		var mark_list = [];
		const prop_list = Object.getOwnPropertyNames(this.marks);
		for(var i in prop_list) {
			const mark = prop_list[i];
			if (this.marks[mark]) mark_list.push(mark);
		}
		return mark_list;
	}
}