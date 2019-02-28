
const e = React.createElement;

export var global_item_count = 1;

// Class to handle basic update functionality
export const Updatable = (Base) => class extends Base {
	constructor(...args) {
		super(...args);
		if (this.state === undefined) this.state = {};
		this._ready = false;
		if (!this.setState) this.setState = (state_change) => this.state = {...this.state, ...state_change};
	}

	update(state_change) {
		if (!this._ready) this.state = {...this.state, ...state_change};
		else this.setState(state_change);
	}
}

// Class to handle event bindings
export const Bindable = (Base) => class extends Updatable(Base) {
	constructor(...args) {
		super(...args);
		this.update({fn_counter : 0});
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

// Class to handle object with toggleable properties (e.g. "active" "hidden" "selected")
export const Classable = (Base) => class extends Bindable(Base) {
	constructor(...args) {
		super(...args);
		this._classnames = [];
		this.update({class : ""});
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

// Class to handle a basic interactable object
export const Interactable = (Base) => class extends Classable(Base) {
	constructor(...args) {
		super(...args);
		this._create_binding("click");
		this._create_binding("key_down");
		this._create_binding("key_up");
		this._create_binding("change");
	}
}

// Class for a basic compendium React component
export class Component extends Interactable(React.Component) {
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
		this.update({...this.state, child_coutner: 0, children: ""});

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