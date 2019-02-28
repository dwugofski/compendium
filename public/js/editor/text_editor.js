
import * as Serialization from "./serialization.js";
import { Component } from "../jclasses.js"

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

export class Editor extends Component {
	constructor(props) {
		super(props);
		this.global_bridge = new EditorBridge();
		this.add_class("mkdn_editor");
		this.add_child(ControlBar, {global_bridge: this.global_bridge});
		this.add_child(CompendiumTextArea, {storage: props.storage, initial: props.initial, global_bridge: this.global_bridge});
	}
}

export class CompendiumTextArea extends React.Component {
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
				console.log(next);
				return next();
		}
	}

	onAddImg(img_url) {
		this.editor.insertBlock({type: 'image', data: {src: img_url}});
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
			case "image":
				return e('img', {...attributes, src: node.data.get('src')});
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