
import { Component } from "../jclasses.js"

const e = React.createElement;

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