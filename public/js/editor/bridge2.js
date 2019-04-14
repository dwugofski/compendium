
import { Classable } from "../jclasses.js"
import { CompendiumTextArea } from "./text_editor.js"

class EditorBridge extends Classable(Object) {
	constructor(...args) {
		super(...args);
		this._area = null;
		this.marks = {};
		this.blocks = {};
		this._create_binding("mark_change");
		this._create_binding("block_change");
		// When the editor changes (e.g. from one position to another) we need to
		// update the controls to reflect the new state; we use the this.change()
		// handlers to communicate that the all existing information may be stale
		this._create_binding("change");
		this.bind_mark_change(this.change);
		this.bind_block_change(this.change);
	}

	get area() {
		return this._area;
	}
	set area(new_area) {
		if (!this.has_area && new_area instanceof CompendiumTextArea) this._area = new_area;
		else if (this.has_area) console.error("Attempted to re-assign the text area of a bridge");
		else throw new Error("Attempted to assign area to invalid object type");
	}
	get has_area() { return this._area !== null; }

	// Function to disable tracking of a mark or block
	_untrack_generic(type, base) {
		// Untrack changes to a 'base' (e.g. 'mark'/'block') called 'type' (e.g. 'bold'/'paragraph')
		if (typeof type == 'string' && type.length > 0 && !this.has_class_toggle(type)) return;

		delete this[base+"s"][type];
		this.remove_class_toggle(type);
	}

	// Track marks
	// this.marks.<type> = getter/setter tied to text area to get state of <type> mark
	// this.make_mark_<type>() = function handlers called when <type> mark is turned on
	// this.un_mark_<type>() = function handlers called when <type> mark is turned off
	// this.mark_change(<type>) = function handlers called when <type> mark mark is turned off or on
	//                            [these functions are called before the specific on/off ones]
	// Text area makes calls to mark change callback. Other items interact through this.marks.<type>
	//     but receive changes through the callbacks
	track_mark(type) {
		if (typeof type == 'string' && type.length > 0 && this.has_class_toggle(type)) return;

		this.add_class_toggle("mark_"+type);
		Object.defineProperty(this.marks, [type], {
			configurable: true,
			get: () => { return this.area.has_mark(type); }
			set: (value) => { if (this["mark_"+type] !== undefined) this.area.set_mark(type, value); }
		});

		this['bind_make_mark_'+type]((() => { this["mark_change"](type); }).bind(this));
		this['bind_un_mark_'+type]((() => { this["mark_change"](type); }).bind(this));
	}
	untrack_mark(type) { this._untrack_generic(type, "mark"); }

	// Track blocks
	// this.blocks.<type> = getter tied to text area to see if current block is <type>
	// this.make_block_<type> = function handlers called when block type set to <type>
	// this.un_block_<type> = function handlers called when block type set to not-<type>
	// this.block_change(<type>) = function handlers called when block type set onto or away from
	//                             <type> [these functions are called before the specific on/off ones]
	// Text area makes calls to block change callback. Other items interact through this.blocks.<type>
	//     but receive changes through the callbacks
	track_block(type) {
		if (typeof type == 'string' && type.length > 0 && this.has_class_toggle(type)) return;

		this.add_class_toggle("block_"+type);
		Object.defineProperty(this.blocks, [type], {
			configurable: true,
			get: () => { return this.area.type == type; }
			set: (value) => {
				// We can only enable a type; we cannot disable it
				if (this["block_"+type] !== undefined &&) {
					if (value && this.area.type != type) this.area.type = type;
					else if (!value) console.error("Cannot set text area block to not-" + type);
				}
			}
		});

		this['bind_make_block_'+type]((() => { this["block_change"](type); }).bind(this));
		this['bind_un_block_'+type]((() => { this["block_change"](type); }).bind(this));
	}
	untrack_block(type) { this._untrack_generic(type, "block"); }

	// Determines whether this block type is being tracked
	tracking_block(type) { return this.blocks[type] !== undefined; }
	// Determines whether this block type is active; uses the this.blocks property
	// if the block is being tracked, otherwise defaults to false
	has_block(type) { return (this.tracking_block(type)) ? this.blocks[type] : false; }
	get type() { return this.area.type; }
	set type(new_type) {
		if (!this.tracking_block(new_type)) return;
		if (this.has_block(new_type)) return;

		this.blocks[new_type] = true;
	}

	// Determines whether this mark type is being tracked
	tracking_mark(type) { return this.mark[type] !== undefined; }
	// Determines whether this mark type is active; uses the this.blocks property
	// if the mark is being tracked, otherwise defaults to false
	has_mark(type) { return (this.tracking_mark(type)) ? this.mark[type] : false; }
	get active_marks() { return this.area.active_marks; }
}