const e = React.createElement;

const PLACEHOLDER_TEXT = 'Edit text here...';

const initialValue = Slate.Value.fromJSON({
	document: {
		nodes: [{
				object: 'block',
				type: 'placeholder',
				nodes: [{
						object: 'text',
						leaves: [{text: PLACEHOLDER_TEXT}]
				}]
			}]
	}
});

var global_item_count = 1;

class Component extends React.Component {
	constructor(props) {
		super(props);
		console.log("Constructing " + this.constructor.name);
		//console.log(props);
		this._cfns = [];
		this._kdfns = [];
		this._kufns = [];
		this._ocfns = [];
		this._classnames = [];
		this.attrs = {
			onChange: this.on_change.bind(this),
			onKeyDown: this.on_key_down.bind(this),
			onKeyUp: this.on_key_up.bind(this),
			onClick: this.on_click.bind(this),
			className: "",
			key: 0
		}
		this.state = {class: "", children: "", child_counter: 0, fn_counter : 0};
		this._ready = false;

		if (typeof props.className == 'string') this.class_name = props.className;
		if (typeof props.children == 'string') this.add_child(props.children);
	}

	get render_src() {
		return "div";
	}
	get element_src() {
		return this.constructor;
	}

	_bind_fn(new_fn, array) {
		array.push(new_fn);

		this.update({fn_counter: this.state.fn_counter + 1});
	}

	_unbind_fn(old_fn, array) {
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

	_on_event(array, event, source, next) {
		for (var i=array.length-1; i>=0; i-=1) {
			array[i](event, source, next);
		}
	}

	bind_click(new_fn) { this._bind_fn(new_fn, this._cfns); }
	bind_key_down(new_fn) { this._bind_fn(new_fn, this._kdfns); }
	bind_key_up(new_fn) { this._bind_fn(new_fn, this._kufns); }
	bind_change(new_fn) { this._bind_fn(new_fn, this._ocfns); }

	unbind_click(old_fn) { this._unbind_fn(old_fn, this._cfns); }
	unbind_key_down(old_fn) { this._unbind_fn(old_fn, this._kdfns); }
	unbind_key_up(old_fn) { this._unbind_fn(old_fn, this._kufns); }
	unbind_change(old_fn) { this._unbind_fn(old_fn, this._ocfns); }

	on_click(event, source, next) { this._on_event(this._cfns, event, source, next); }
	on_key_down(event, source, next) { this._on_event(this._kdfns, event, source, next); }
	on_key_up(event, source, next) { this._on_event(this._kufns, event, source, next); }
	on_change(event, source, next) { this._on_event(this._ocfns, event, source, next); }

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
		return this._classnames.includes(class_name);
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

	add_child(constructor, props = {}, text = undefined) {
		//global global_item_count;
		console.log("Adding "+constructor.name);
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
			if (text) child = e(constructor, props, text);
			else child = e(constructor, props);
			children.push(child);
			counter += 1;
			global_item_count += 1;
			
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
		console.log("rendering "+this.constructor.name);
		console.log(this.state.children);
		if (!this._ready) this._ready = true;
		return e(this.render_src, {...this.props, ...this.attrs, className: this.state.class}, this.state.children);
	}

	update(state_change) {
		if (!this._ready) {
			this.state = {...this.state, ...state_change};
		} else {
			this.setState(state_change);
		}
	}
}

class ControlOption extends Component {
	constructor(props) {
		super(props);
		this.add_class("option fl");
		this.bind_click(this.toggle_activate.bind(this));
		this.activated = false;
	}

	verify_activation() {
		this.activated = this.has_class("active");
	}

	toggle_activate() {
		console.log("Toggle active");
		this.verify_activation();
		if (this.activated) this.deactivate();
		else this.activate();
	}

	activate() {
		this.verify_activation();
		if (!this.activated) {
			this.activated = true;
			this.add_class("active");
		}
	}

	deactivate() {
		this.verify_activation();
		if (this.activated) {
			this.activated = false;
			this.remove_class("active");
		}
	}
}

class ControlBar extends Component {
	constructor(props) {
		super(props);
		this.add_class("control_bar");
		this.add_child(ControlOption, {}, "Option 1");
		this.add_child(ControlOption, {}, "Option 2");
		this.add_child(ControlOption, {}, "Option 3");
		const clearer = this.add_child(Component, {className: "clearer"});
	}
}

class Editor extends Component {
	constructor(props) {
		super(props);
		this.add_class("mkdn_editor");
		this.add_child(ControlBar);
		//this.add_child(new CompendiumTextArea());
		/*this.children = [];
		console.log("Making");
		this.children.push(e(CompendiumControlBar, {key: 1}));
		this.children.push(e(CompendiumTextArea, {key: 2}));*/
	}
}

class CompendiumTextArea extends React.Component {
	constructor() {
		super();
		this.state = {value: initialValue};
		this.has_text = false;
	}

	render() {
		//console.log(this.state.value.toJSON());
		return e(
			SlateReact.Editor,
			{	value: this.state.value,
				onChange: this.onChange.bind(this),
				onKeyDown: this.onKeyDown.bind(this),
				renderMark: this.renderMark.bind(this),
				renderNode: this.renderNode.bind(this),
				className: (this.has_text) ? "comp_editor" : "comp_editor empty"
			});
	}

	onChange({value}) {
		const {text, nodes} = value.document;
		this.has_text = ((text != PLACEHOLDER_TEXT || (nodes.size >= 2 && nodes.get(1).type != "paragraph")) || nodes.size > 2) ;
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
		/*if (!this.has_text && editor.value.startBlock.type == "placeholder") {
			editor.moveFocusToEndOfDocument();
			console.log(editor.value.document.nodes.size);
			if (editor.value.document.nodes.size < 2) editor.splitBlock().setBlocks('paragraph');
		}*/

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
					//console.log(editor);
				}
				return next();
		}
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

		editor.setBlocks(type);
		console.log(type);
		// Wrap list if necessary
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
		editor.setBlocks('paragraph');

		// Unwrap list if necessary
		switch (startBlock.type) {
			case 'oli':
				editor.unwrapBlock('ol');
				break;
			case 'uli':
				editor.unwrapBlock('ul');
				break;
			default:
				break;
		}
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
			editor.setBlocks('paragraph');

			switch (type) {
				case 'oli':
					editor.unwrapBlock('ol');
					break;
				case 'uli':
					editor.unwrapBlock('ul');
					break;
				default:
					break;
			}
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

export function init() {
	if ($("#page_form_text")[0]) {
		ReactDOM.render(
			e(Editor, {key: 0}),
			$("#page_form_text")[0]
		);
	}
}

$(document).ready(init);
