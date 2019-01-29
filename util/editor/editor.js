
import * as Serialization from "./serialization.js";

const e = React.createElement;

const PLACEHOLDER_TEXT = 'Edit text here...';

const INITIAL_VALUE = Slate.Value.fromJSON({
	document: {
		nodes: [{
				object: 'block',
				type: 'placeholder',
				nodes: [{
						object: 'text',
						leaves: [{text: PLACEHOLDER_TEXT}]
				}]
			},{
				object: 'block',
				type: 'paragraph',
				nodes: [{object: 'text', leaves: [{text: ""}]}]
			}]
	}
});

var global_item_count = 1;

const Updatable = (Base) => class extends Base {
	constructor(...args) {
		super(...args);
		if (this.state === undefined) this.state = {};
		this._ready = false;
		if (!this.setState) this.setState = (state_change) => this.state = state_change;
	}

	update(state_change) {
		if (!this._ready) {
			this.state = {...this.state, ...state_change};
		} else {
			this.setState(state_change);
		}
	}
}

const Bindable = (Base) => class extends Updatable(Base) {
	constructor(...args) {
		super(...args);
		if (this.state === undefined) this.state = {};
		this.state = {...this.state, child_counter: 0, fn_counter : 0};
	}

	_bind_fn(new_fn, binding_name) {
		if (!this._has_binding(binding_name)) return;
		const array = this[binding_name+"_fns"];
		array.push(new_fn);

		this.update({fn_counter: this.state.fn_counter + 1});
	}

	_unbind_fn(old_fn, binding_name) {
		if (!this._has_binding(binding_name)) return;
		const array = this[binding_name+"_fns"];
		var indexes = [];
		for (var i=0; i<array.length; i+=1) {
			if (old_fn === array[i]) {
				indexes.push(i);
			}
		}

		for (var i=indexes.length-1; i>=0; i-=1) {
			array.splice(indexes[i], 1);
		}
		this.update({fn_counter: this.state.fn_counter - 1});
	}

	_on_event(binding_name, ...args) {
		if (!this._has_binding(binding_name)) return;
		const array = this[binding_name+"_fns"];
		for (var i=array.length-1; i>=0; i-=1) {
			array[i](...args);
		}
	}

	_create_binding(binding_name) {
		if (this._has_binding(binding_name)) return;
		this[binding_name+"_fns"] = [];
		this["bind_"+binding_name] = (new_fn) => { this._bind_fn(new_fn, binding_name); };
		this["unbind_"+binding_name] = (old_fn) => { this._unbind_fn(old_fn, binding_name); };
		this["on_"+binding_name] = (...args) => { this._on_event(binding_name, ...args); };
		this[binding_name] = (...args) => { this._on_event(binding_name, ...args); };
	}

	_destroy_binding(binding_name) {
		if (!this._has_binding(binding_name)) return;
		this[binding_name+"_fns"] = undefined;
		this["bind_"+binding_name] = undefined;
		this["unbind_"+binding_name] = undefined;
		this["on_"+binding_name] = undefined;
		this[binding_name] = undefined;
	}

	_has_binding(binding_name) {
		return (this[binding_name+"_fns"] !== undefined);
	}

}

const Classable = (Base) => class extends Bindable(Base) {
	constructor(...args) {
		super(...args);
		this._classnames = [];
		if (this.state === undefined) this.state = {};
		this.state = {...this.state, class: ""};
	}

	add_class_toggle(class_name) {
		class_name = class_name.replace(" ", "_");
		this._create_binding("make_"+class_name);
		this._create_binding("un_"+class_name);
		this['is_'+class_name] = () => { return this.has_class(class_name); };
		this['make_'+class_name] = () => {
			if (!this['is_'+class_name]()) {
				this.add_class(class_name);
				this['on_make_'+class_name]();
			}
		};
		this['un_'+class_name] = () => {
			if (this['is_'+class_name]()) {
				this.remove_class(class_name);
				this['on_un_'+class_name]();
			}
		};
		this['toggle_'+class_name] = () => {
			if (this['is_'+class_name]()) this['un_'+class_name]();
			else this['make_'+class_name]();
		};
		Object.defineProperty(this, class_name, {
			get: function() { return this['is_'+class_name](); },
			set: function(val) {
				if (val) this['make_'+class_name]();
				else this['un_'+class_name]();
			},
			configurable: true
		});
	}

	remove_class_toggle(class_name) {
		class_name = class_name.replace(" ", "_");
		this._destroy_binding("make_"+class_name);
		this._destroy_binding("un_"+class_name);
		this['is_'+class_name] = undefined;
		this['make_'+class_name] = undefined;
		this['un_'+class_name] = undefined;
		this['toggle_'+class_name] = undefined;
		delete this[class_name];
	}

	has_class_toggle(class_name) {
		class_name = class_name.replace(" ", "_");
		return (this['toggle_'+class_name] !== undefined);
	}

	add_class(class_name) {
		const classes = class_name.split(" ");
		for (var i=0; i<classes.length; i+=1) {
			if (this.has_class(classes[i])) continue;

			this._classnames.push(classes[i]);
		}

		this.update({class: this.class_name});
	}

	remove_class(class_str) {
		const classes = class_str.split(" ");
		for (var i=0; i<classes.length; i+=1) {
			const class_name = classes[i];
			var indexes = [];
			for (var j=0; j<this._classnames.length; j+=1) {
				if (class_name == this._classnames[j]) {
					indexes.push(j);
				}
			}

			for (var j=indexes.length-1; j>=0; j-=1) {
				this._classnames.splice(indexes[j], 1);
			}
		}
		
		this.update({class: this.class_name});
	}

	has_class(class_name) {
		var ret = true;
		const class_names = class_name.split(" ");
		for (var i=0; i<class_names.length; i+=1) {
			if (!this._classnames.includes(class_names[i])) {
				ret = false;
				break;
			}
		}
		return ret;
	}

	get class_name() {
		var class_str = "";
		var prefix = "";
		for (var i=0; i<this._classnames.length; i+=1) {
			class_str += prefix + this._classnames[i];
			if (i == 0) prefix = " ";
		}
		return class_str;
	}
	set class_name(class_str) {
		const classes = class_str.split(" ");
		this._classnames = [];
		for (var i=0; i<classes.length; i+=1) {
			this._classnames.push(classes[i]);
		}

		this.update({class: this.class_name});
	}
}

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

//const global_bridge = new EditorBridge();

const Interactable = (Base) => class extends Classable(Base) {
	constructor(...args) {
		super(...args);
		if (this.state === undefined) this.state = {};
		this.state = {...this.state, child_counter: 0, fn_counter : 0};
		this._create_binding("click");
		this._create_binding("key_down");
		this._create_binding("key_up");
		this._create_binding("change");
	}
}

class Component extends Interactable(React.Component) {
	constructor(props) {
		super(props);
		this.attrs = {
			onChange: this.on_change.bind(this),
			onKeyDown: this.on_key_down.bind(this),
			onKeyUp: this.on_key_up.bind(this),
			onClick: this.on_click.bind(this),
			className: "",
			key: 0
		}
		if (this.state === undefined) this.state = {};
		this.state = {...this.state, children: ""};

		if (typeof props.className == 'string') this.class_name = props.className;
		if (typeof props.children == 'string') this.add_child(props.children);

		if (typeof props.fns == 'object') {
			for (var event in props.fns) {
				if (!this._has_binding(event)) this._create_binding(event);
				const fns = props.fns[event];

				if (typeof fns == 'function') this["bind_"+event](fns);
				else if (typeof fns == 'object' && fns.constructor.name == "Array") {
					for (var i=0; i<fns.length; i+=1) {
						const fn = fns[i];
						if (typeof fn == 'function') this["bind_"+event](fns[i]);
						else console.error("An item in props.fns."+event+" array is not a function");
					}
				} else console.error("Property '"+event+"' in props.fns is not an array or function");
			}
		}
	}

	get render_src() {
		return "div";
	}
	get element_src() {
		return this.constructor;
	}

	add_child(constructor, props = {}, text = undefined) {
		//global global_item_count;
		var children = this.state.children.slice();
		var counter = this.state.child_counter;
		var child = null;
		if (typeof constructor == "string" && counter == 0) {
			children = constructor;
			child = constructor;
		}
		else {
			if (counter == 0) children = [];

			props.key = global_item_count;
			props.index = global_item_count;
			if (text) child = e(constructor, props, text);
			else child = e(constructor, props);
			children.push(child);
			counter += 1;
			global_item_count += 1;
			
		}

		this.update({children: children, child_counter: counter});
		return child;
	}

	remove_child(key) {
		//global global_item_count;
		var children = this.state.children.slice();
		var counter = this.state.child_counter;
		var index = null;
		var child = null;

		if (children.length == 0) return;
		else if (children.length == 1 && typeof children[0] == 'string') {
			children = [];
			counter = 0;
		} else if (key !== undefined) {
			for (var i in children) {
				if (children[i].props.key == key) {
					index = i;
					break;
				}
			}

			if (index) {
				child = children[i];
				children.splice(index, 1);
				counter = counter - 1;
			}
		}

		this.update({children: children, child_counter: counter});
		return child;
	}

	get childs() {
		var childs = [];
		const children = this.state.children
		for (var i=0; i<children.length; i+=1) {
			const child = children[i];
			childs.push(child.element);
		}
		return childs;
	}

	get element() {
		return e(this.element_src, {...this.props, ...this.attrs} , this.state.children);
	}

	render() {
		if (!this._ready) this._ready = true;
		return e(this.render_src, {...this.attrs, className: this.state.class}, this.state.children);
	}
}

class ControlOption extends Component {
	constructor(props) {
		super(props);
		//this.add_class("option fl");
		this.add_class("option");
		this.add_class_toggle("active");
		//this.activated = false;
		this._create_binding("mouse_down");
		this.bind_mouse_down((event) => { event.preventDefault(); });
		this.attrs.onMouseDown = this.on_mouse_down.bind(this);
	}
}

class MarkOption extends ControlOption {
	constructor(props) {
		if (props === undefined) props = {};
		if (props.mark === undefined) props.mark = "";
		super(props);
		this.add_class("mark");
		this.bind_click(((event) => {
			event.preventDefault();
			this.toggle_active();
		}).bind(this));
		this.props.global_bridge.track_mark(this.props.mark);
		this.bind_make_active(() => {
			const mark = this.props.mark;
			this.props.global_bridge[mark] = true;
		});
		this.bind_un_active(() => {
			const mark = this.props.mark;
			this.props.global_bridge[mark] = false;
		});
		this.props.global_bridge["bind_make_"+this.props.mark]( (() => { if(!this.active) this.active = true;}).bind(this) );
		this.props.global_bridge["bind_un_"+this.props.mark]( (() => { if(this.active) this.active = false;}).bind(this) );
	}
}

class DropDownOptionDisplay extends Component {
	constructor(props) {
		super(props);
		this.add_class("dropdown-toggle");
		this.add_class("dropdown_display");
		if (this.attrs === undefined) this.attrs = {};
		this.attrs["data-toggle"] = "dropdown";
		this.bind_click(this.parent.expand.bind(this.parent));
		this.parent.bind_set_selection( ((name, val) => this.value = val).bind(this) );
		this.parent.display = this;

		if (this.props.initial) this.parent.selection = {name: this.props.initial, value: this.parent.props.options[this.props.initial]};
		else this.value = this.props.blank;

		this._create_binding("mouse_down");
		this.bind_mouse_down((event) => { event.preventDefault(); });
		this.attrs.onMouseDown = this.on_mouse_down.bind(this);
	}

	get parent() { return this.props.parent; }
	get render_src() { return "span"; }

	get value() { return this.state.children[0].replace(" \u25BC", ""); }
	set value(val) {
		if (typeof val == 'string') this.update({children: [val + " \u25BC"]});
		else this.update({children: [this.props.blank + " \u25BC"]});
	}

}

class DropDownOptionItem extends Component {
	constructor(props) {
		super(props);
		this.add_class("dd_opt_"+this.props.name);
		this.bind_click( (event => this.parent.selection = {name: this.props.name, value: this.props.text}).bind(this) );

		this._create_binding("mouse_down");
		this.bind_mouse_down((event) => { event.preventDefault(); });
		this.attrs.onMouseDown = this.on_mouse_down.bind(this);
	}

	get parent() { return this.props.parent; }
	get render_src() { return "li"; }
}

class DropDownOptionList extends Component {
	constructor(props) {
		if (props === undefined) props = {};
		const options = (typeof props.options != 'object') ? {} : props.options;
		super(props);
		this.add_class("dropdown-menu");
		this._options = {};

		for (var key in options)
			this.add_option(key, options[key]);
	}

	add_option(name, option) {
		if (Object.getOwnPropertyNames(this._options).includes(name)) return;

		const new_option = this.add_child(DropDownOptionItem, {parent: this.parent, name: name, text: option}, option);
		Object.defineProperty(this._options, name, {
			configurable: true,
			value: {
				key: new_option.props.index,
				text: option
			}
		});
	}

	remove_option(name) {
		if (!Object.getOwnPropertyNames(this._options).includes(name)) return;

		this.remove_child(this._options[name].key);
		delete this._options[name];
	}

	get parent() { return this.props.parent; }
	get render_src() { return "ul"; }
}

class DropDownOption extends ControlOption {
	constructor(props) {
		if (props === undefined) props = {};
		super(props);
		this.add_class("dropdown");
		this._create_binding("set_selection");
		this._create_binding("expand");

		this.add_child(DropDownOptionList, {parent: this, options: this.props.options});
		this.add_child(DropDownOptionDisplay, {parent: this, initial: this.props.initial, blank: this.props.blank}, this.props.blank + " \u25BC");

		this._create_binding("mouse_down");
		this.bind_mouse_down((event) => { event.preventDefault(); });
		this.attrs.onMouseDown = this.on_mouse_down.bind(this);
	}

	get selection() { return this.display.value; }
	set selection({name, value}) {
		if (this.display === undefined || this.selection == value) return;

		this.set_selection(name, value);
	}
}

class BlockTypeOption extends DropDownOption {
	constructor(props) {
		super(props);

		for (var block in this.props.options)
			this.props.global_bridge.track_block(block);

		this.props.global_bridge.bind_block_change(((type) => {
			if (this.props.options[type] !== undefined) this.selection = {name: type, value: this.props.options[type]};
			else console.log("Do not have type "+type);
		}).bind(this));

		//this.selection = {name: "paragraph", value: this.props.options.paragraph};
		this.props.global_bridge["paragraph"] = true;
	}
}

class AddImgOption extends ControlOption {
	constructor(props) {
		super(props);
		console.log(this);
		this.bind_click(this.show.bind(this));
		$("#img_add_screen").click(((event) => {
			if (event.target == $("#img_add_screen")[0]) {
				this.hide(event);
			}
		}).bind(this));

		$('#img_add_form_submit').click(this.submit.bind(this));
	}

	show() {
		$("#img_add_screen").fadeIn("fast");
	}

	hide() {
		$("#img_add_screen").fadeOut("fast");
	}

	complete(url, condition) {
		if (condition == 'success') this.props.global_bridge.on_add_img(url);
	}

	submit() {
		const timeout = 5000;
		const callback = this.complete.bind(this);
		const url = $('#img_add_form_src').val();
		var timedOut = false, timer;
		var img = new Image();
		img.onerror = img.onabort = function() {
			if (!timedOut) {
				clearTimeout(timer);
				callback(url, "error");
			}
		};
		img.onload = function() {
			if (!timedOut) {
				clearTimeout(timer);
				callback(url, "success");
			}
		};
		img.src = url;
		timer = setTimeout(function() {
			timedOut = true;
			// reset .src to invalid URL so it stops previous
			// loading, but doesn't trigger new load
			img.src = "//!!!!/test.jpg";
			callback(url, "timeout");
		}, timeout); 
	}
}

class ControlBar extends Component {
	constructor(props) {
		super(props);
		this.add_class("control_bar");
		this.add_child(BlockTypeOption, {
			options: {
				paragraph: "Paragraph",
				h1: "Heading 1",
				h2: "Heading 2",
				h3: "Heading 3",
				h4: "Heading 4",
				h5: "Heading 5",
				h6: "Heading 6",
				oli: "Numbered List",
				uli: "Bullet List"
			},
			fns: {
				set_selection: [(name, value) => { this.props.global_bridge.active_block = name; }]
			}, 
			initial: 'paragraph',
			blank: "\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0",
			global_bridge: this.props.global_bridge
		});
		this.add_child(MarkOption, {className: "bold", mark: "bold", global_bridge: this.props.global_bridge}, "B");
		this.add_child(MarkOption, {className: "italic", mark: "italic", global_bridge: this.props.global_bridge}, "I");
		this.add_child(MarkOption, {className: "underlined", mark: "underlined", global_bridge: this.props.global_bridge}, "U");
		this.add_child(ControlOption, {className: "bar"});
		this.add_child(AddImgOption, {className: "add_img", global_bridge: this.props.global_bridge}, "Add Image");
		const clearer = this.add_child(Component, {className: "clearer"});
		this.bind_click(event => event.preventDefault());
	}
}

export class Editor extends Component {
	constructor(props) {
		super(props);
		this.global_bridge = new EditorBridge();
		this.add_class("mkdn_editor");
		this.add_child(ControlBar, {global_bridge: this.global_bridge});
		this.add_child(CompendiumTextArea, {storage: props.storage, initial: props.initial, global_bridge: this.global_bridge});
	}
}

class CompendiumTextArea extends React.Component {
	constructor(props) {
		super(props);
		//this.state = {value: initialValue};
		this.has_text = false;
		this._ready = false;
		this.editor == undefined;
		this.storage = (props.storage && typeof props.storage == 'string') ? props.storage : null;
		console.log(props.initial);

		this.state = {value: (props.initial !== undefined) ? props.initial : INITIAL_VALUE};
		if (this.storage !== null) localStorage.setItem(this.storage, Serialization.serialize_value(this.state.value));

		this.props.global_bridge.bind_mark_change( ((type) => {
			if (this.editor === undefined) return;
			if (this.props.global_bridge[type] && !this.editor.value.activeMarks.some(mark => mark.type == type)) this.editor.toggleMark(type);
			else if (!this.props.global_bridge[type] && this.editor.value.activeMarks.some(mark => mark.type == type)) this.editor.toggleMark(type);
		}).bind(this));

		this.props.global_bridge.bind_block_change( ((type) => {
			if (this.editor.value.startBlock !== null && this.editor.value.startBlock.type !== 'placeholder') this.set_type(this.editor, type);
		}).bind(this));

		this.props.global_bridge.bind_add_img(this.onAddImg.bind(this));
	}

	ref(editor) {
		this.editor = editor;
	}

	render() {
		return e(
			SlateReact.Editor,
			{	value: this.state.value,
				onChange: this.onChange.bind(this),
				onKeyDown: this.onKeyDown.bind(this),
				renderMark: this.renderMark.bind(this),
				renderNode: this.renderNode.bind(this),
				ref: this.ref.bind(this),
				className: (this.has_text) ? "comp_editor" : "comp_editor empty"
			});
	}

	onChange({value}) {
		const {text, nodes} = value.document;
		this.has_text = ((text != PLACEHOLDER_TEXT || (nodes.size >= 2 && nodes.get(1).type != "paragraph")) || nodes.size > 2);

		if (value.startBlock !== null) {
			if (this.props.global_bridge.active_block != value.startBlock.type) this.props.global_bridge.active_block = value.startBlock.type;
		}

		const global_marks = this.props.global_bridge.active_marks;
		for (var i in global_marks) {
			const type = global_marks[i];
			if (!value.activeMarks.some(mark => mark.type == type) && this.props.global_bridge[type]) {
				this.props.global_bridge[type] = false;
			}
		}

		const my_marks = value.activeMarks.toArray();
		for (var i in my_marks) {
			const type = my_marks[i].type;
			if (!this.props.global_bridge[type] && value.activeMarks.some(mark => mark.type == type)) this.props.global_bridge[type] = true;
		}

		if (this.storage) {
			if (value.document != this.state.value.document) {
				localStorage.setItem(this.storage, Serialization.serialize_value(value));
			}
		}

		this.setState({value});
	}

	onKeyDown(event, editor, next) {
		if (!this.has_text) {
			if (editor.value.startBlock.type == "placeholder") {
				editor.moveFocusToEndOfDocument();
				if (editor.value.document.nodes.size < 2) editor.splitBlock().setBlocks('paragraph');
			}
			if (event.key == "ArrowLeft" || event.key == "ArrowUp") {
				event.preventDefault();
				const ret = next();
				editor.moveFocusToEndOfDocument();
				return ret;
			}
			if (event.key == "Backspace" || event.key == "Delete") {
				event.preventDefault();
				return;
			}
		}

		switch(event.key) {
			case 'Enter':
				return this.onEnter(event, editor, next);
			case ' ':
				return this.onSpace(event, editor, next);
			case 'Backspace':
				return this.onBackspace(event, editor, next);
			case 'Delete':
				return this.onDelete(event, editor, next);
			case 'Tab':
				event.preventDefault();
				editor.insertText("\t");
			default:
				if (event.ctrlKey && !event.repeat) {
					var mark = undefined;
					switch(event.key) {
						case 'b':
							mark = 'bold';
							break;
						case 'i':
							mark = 'italic';
							break;
						case 'u':
							mark = 'underlined';
							break;
						default:
							return next();
					}
					event.preventDefault();
					editor.toggleMark(mark);
				}
				return next();
		}
	}

	onAddImg(img_url) {
		this.editor.insertBlock('img ' + img_url);
	}

	onEnter(event, editor, next) {
		const {value} = editor;
		const {selection} = value;
		const start_block = value.startBlock;
		const {start, end, expanded} = selection;
		if (expanded) return next();

		switch(start_block.type){
			case "uli":
			case "oli":
				if (start.offset == 0 && start_block.text.length == 0) return this.onBackspace(event, editor, next);
				else if (event.shiftKey) {
					event.preventDefault();
					editor.insertText("\n");
				} else return next();
				break;
			case "paragraph":
				console.log("p");
				if (event.shiftKey) {
					event.preventDefault();
					editor.insertText("\n");
				} else return next();
				break;
			default:
				event.preventDefault();
				editor.splitBlock().setBlocks('paragraph');
				console.log("p");
				break;
		}
	}

	set_type(editor, type) {
		editor.unwrapBlock("ol");
		editor.unwrapBlock("ul");
		editor.setBlocks(type);
		switch (type) {
			case 'oli':
				editor.wrapBlock('ol');
				break;
			case 'uli':
				editor.wrapBlock('ul');
				break;
			default:
				break;
		}
	}

	onSpace(event, editor, next) {
		const { value } = editor;
		const { selection } = value;
		// If we have an expanded output, we need to collapse it before determining whether we have a shortcut
		const options = (selection.isExpanded) ? next() || [] : null;

		const { startBlock } = value;
		const { start } = selection;
		// Gets rid of everything after and including the space in the heading
		const chars = startBlock.text.slice(0, start.offset).replace(/\s*/g, '');
		const type = MarkdownParser.determine_block(chars);
		if (!type) return options ? options : next();

		event.preventDefault();
		this.set_type(editor, type);

		editor.moveFocusToStartOfNode(startBlock).delete()
	}

	onBackspace(event, editor, next) {
		const { value } = editor;
		const { selection } = value;
		if (selection.isExpanded) return next();
		if (selection.start.offset != 0) return next();

		const { startBlock } = value;
		if (startBlock.type == 'paragraph') return next();

		event.preventDefault();
		this.set_type(editor, 'paragraph');
	}

	onDelete(event, editor, next) {
		const {value} = editor;
		//const {text, nodes} = value.document;
		const {selection} = value;
		const {text, type} = value.startBlock;

		if (selection.isExpanded) return next();
		if (selection.start.offset != 0) return next();

		if (text == "" && type != "paragraph") {
			event.preventDefault();
			this.set_type(editor, 'paragraph');
		} else return next();
	}

	renderNode(props, editor, next) {
		const { attributes, children, node } = props

		switch(node.type) {
			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "ul":
			case "ol":
				return e(node.type, attributes, children);
			case "oli":
			case "uli":
				return e('li', attributes, children);
			case "paragraph":
				return e("p", attributes, children);
			case "placeholder":
				return e("div", {...attributes, 'className': 'placeholder'}, children);
			case "break":
				return e("span", attributes, children);
			default:
				if (node.type.substring(0, 3) == "img") {
					return e("img", {...attributes, 'src': node.type.substring(4)});
				}
				return next();
		}
	}

	renderMark(props, editor, next) {
		const { children, mark, attributes } = props;

		switch(mark.type) {
			case "bold":
				return e("strong", attributes, children);
			case "italic":
				return e("em", attributes, children);
			case "underlined":
				return e("u", attributes, children);
			default:
				return next();
		}
	}
}

const headings_ident = /^#{1,6}$/g;
const ordered_list_ident = /^((( {3})*|\t)*[0-9]+\.)$/g;
const unordered_list_ident = /^((( {3})*|\t)*\*+)$/g;

class MarkdownParser {
	static determine_block(prefix) {
		if (prefix.match(headings_ident)) return 'h' + prefix.length;
		else if (prefix.match(ordered_list_ident)) return 'oli';
		else if (prefix.match(unordered_list_ident)) return 'uli';
		else return null;
	}
}

export {Serialization};
