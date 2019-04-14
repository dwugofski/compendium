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
		this.update({_bindable_counter : 0});
	}

	_bindable_has(event_name) {
		return this[event_name + "_fns"] === undefined
	}

	_bindable_bind(new_fn, event_name) {
		if (!this._bindable_has(event_name)) return;
		const array = this[event_name + "_fns"];
		array.push(new_fn);
		this.update({_bindable_counter: this.state._bindable_counter + 1});
	}

	_bindable_unbind(old_fn, event_name) {
		if (!this._bindable_has(event_name)) return;
		const array = this[event_name+"_fns"];
		var indexes = [];
		for (var i=0; i<array.length; i+=1) {
			if (old_fn === array[i]) {
				indexes.push(i);
			}
		}

		for (var i=indexes.length-1; i>=0; i-=1) {
			array.splice(indexes[i], 1);
		}
		this.update({_bindable_counter: this.state._bindable_counter - indexes.length});
	}

	_bindable_on(event_name, ...args) {
		if (!this._has_binding(event_name)) return;
		const array = this[event_name+"_fns"];
		for (var i=array.length-1; i>=0; i-=1) {
			array[i](...args);
		}
	}

	_bindable_create(event_name) {
		if (this._bindable_has(event_name)) return;
		this[event_name + "_fns"] = [];
		this["bind_" + event_name] = ((new_fn) => this._bindable_bind(new_fn, event_name)).bind(this);
		this["on_" + event_name] = ((new_fn) => this._bindable_bind(new_fn, event_name)).bind(this);
		this["unbind_" + event_name] = ((old_fn) => this._bindable_unbind(old_fn, event_name)).bind(this);
		this[event_name] = ((...args) => this._bindable_on(event_name, ...args)).bind(this);
	}

	_bindable_destroy(event_name) {
		if (!this._bindable_has(event_name)) return;
		this[event_name + "_fns"] = undefined;
		this["bind_" + event_name] = (() => this._bindable_destoryed_error(event_name, "bind_"));
		this["on_" + event_name] = (() => this._bindable_destoryed_error(event_name, "on_"));
		this["unbind_" + event_name] = (() => this._bindable_destoryed_error(event_name, "unbind_"));
		this[event_name] = (() => this._bindable_destoryed_error(event_name));
	}

	_bindable_destoryed_error(event_name, fn_prefix = "") {
		console.error(`Cannot call ${fn_prefix + event_name}; event ${event_name} no longer registered.`);
	}

	register_event(event_name) { return this._bindable_create(event_name); }
	deregister_event(event_name) { return this._bindable_destroy(event_name); }
	has_event(event_name) { return this._bindable_has(event_name); }
}

// Class to handle object with toggleable properties (e.g. "active" "hidden" "selected")
export const Classable = (Base) => class extends Bindable(Base) {
	constructor(...args) {
		super(...args);
		this._classable_classes = [];
		this.update({_classable_name : ""});
	}

	_classable_make(class_name) {
		
	}

	track_class(class_name) {
		class_name = class_name.replace(" ", "_");
		this.register_event("make_"+class_name);
		this.register_event("un_"+class_name);
		this['is_'+class_name] = () => { return this.has_class(class_name); };
		this['make_'+class_name] = () => {
			if (!this['is_'+class_name]()) {
				this.add_class(class_name);
				this['make_'+class_name]();
			}
		};
		this['un_'+class_name] = () => {
			if (this['is_'+class_name]()) {
				this.remove_class(class_name);
				this['un_'+class_name]();
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
}