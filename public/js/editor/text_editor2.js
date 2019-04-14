
import React from 'react'
import { Editor } from 'slate-editor'
import * as Serialization from "./serialization.js"
import { Component } from "../jclasses.js"
import { EditorBridge } from "./bridge.js"
import { ControlBar } from "./control_bar.js"

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

class CompendiumEditor extends Component {
	constructor(structure) {
		const { props, children, area } = structure;
		super(props);
		if (children) this.add_children(children);
		this.bridge = new EditorBridge();
		area.bridge = this.bridge;
		this.editor = new CompendiumTextArea(area);
		this.bridge.area = this.editor;
	}
}

class CompendiumTextArea extends React.Component {
	constructor(props) {
		super(props);
		//this.state = {value: initialValue};
		this.has_text = false;
		this._ready = false;
		this.editor == null;
		this.storage = (props.storage && typeof props.storage == 'string') ? props.storage : null;
		this.bridge = this.props.bridge;

		this.state = { value: (props.initial !== undefined) ? props.initial : INITIAL_VALUE };
		if (this.storage !== null) localStorage.setItem(this.storage, Serialization.serialize_value(this.state.value));
	}

	ref(editor) {
		this.editor = editor;
	}

	get type() {
		if ( this.editor && this.editor.value.startBlock ) return this.editor.value.startBlock.type;
		else return null;
	}
	set type(new_type) {
		const editor = this.editor;
		const curr_type = this.type;
		editor.unwrapBlock("ol");
		editor.unwrapBlock("ul");
		editor.setBlocks(new_type);
		switch (new_type) {
			case 'oli':
				editor.wrapBlock('ol');
				break;
			case 'uli':
				editor.wrapBlock('ul');
				break;
			default:
				break;
		}
		if (this.type != this.curr_type) {
			this.bridge["un_block_" + curr_type];
			this.bridge["make_block_" + this.type];
		}
	}

	has_mark(type) {
		if (this.editor === null) return false;
		else return this.editor.value.activeMarks.some(mark => mark.type == type);
	}
	set_mark(type, add=true) {
		if (add) {
			if (this.has_mark(type)) return;
			else {
				this.editor.toggleMark(type);
				this.bridge["make_mark_"+type]();
			}
		} else {
			if (!this.has_mark(type)) return;
			else {
				this.editor.toggleMark(type);
				this.bridge["un_mark_"+type]();
			}
		}
	}
	toggle_mark(type) {
		this.set_mark(type, this.has_mark(type));
	}
	get active_marks() {
		if (this.editor === null) return [];
		else return this.editor.value.activeMarks.toJS();
	}

	render() {
		return <Editor 
			value={this.state.value}
			onChange={this.on_change.bind(this)}
			onKeyDown={this.on_key_down.bind(this)}
			renderMark={this.render_mark.bind(this)}
			renderNode={this.render_node.bind(this)}
			className={(this.has_text) ? "comp_editor" : "comp_editor empty"} 
			ref={this.ref.bind(this)} 
		/>
	}


	change_block(type) {
		if (this.editor === null) return;
		if ( this.type && this.type !== 'placeholder' ) this.type = type;
	}

	on_change({value}) {
		const {text, nodes} = value.document;
		this.has_text = ((text != PLACEHOLDER_TEXT || (nodes.size >= 2 && nodes.get(1).type != "paragraph")) || nodes.size > 2);

		this.bridge.change();

		// Currently disabling storage for serialization issues
		// if (this.storage) {
		// 	if (value.document != this.state.value.document) {
		// 		localStorage.setItem(this.storage, Serialization.serialize_value(value));
		// 	}
		// }

		this.setState({value});
	}

	on_key_down(event, editor, next) {
		if ( !this.has_text ) {
			if (this.type == "placeholder") {
				editor.moveFocusToEndOfDocument();
				if (editor.value.document.nodes.size < 2) {
					editor.splitBlock();
					this.type = "paragraph";
				}
			}
			if (event.key == "ArrowLeft" || event.key == "ArrowUp") {
				event.preventDefault();
				const ret = next();
				editor.moveFocusToEndOfDocument();
				return ret;
			}
			else if (event.key == "Backspace" || event.key == "Delete") {
				event.preventDefault();
				return;
			}
		}

		switch(event.key) {
			case 'Enter':
				return this.on_enter(event, editor, next);
			case ' ':
				return this.on_space(event, editor, next);
			case 'Backspace':
				return this.on_backspace(event, editor, next);
			case 'Delete':
				return this.on_delete(event, editor, next);
			case 'Tab':
				event.preventDefault();
				editor.insertText("\t");
				// Purposefully fall through
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
					this.toggle_mark(mark);
				}
				return next();
		}
	}

	on_add_image(img_url) {
		this.editor.insertBlock({type: 'image', data: {src: img_url}});
		// May need to do some cleanup calls here...
		// e.g. this.on_change(this.editor)?
		// Will probably need to update the schema to work with images
		this.on_change(this.editor);
	}

	on_enter(event, editor, next) {
		// On enter, break off into new paragraph
		const {value} = editor;
		const {selection} = value;
		const start_block = value.startBlock;
		const {start, end, expanded} = selection;
		if (expanded) return next();

		switch(this.type){
			case "uli":
			case "oli":
				// For list items, create new list items
				// Unless the list item is empty, then change to paragraph
				if (start.offset == 0 && start_block.text.length == 0) this.type = 'paragraph';
				else if (event.shiftKey) {
					// If pressing shift, don't create new list item, just break
					event.preventDefault();
					editor.insertText("\n");
				} else return next();
				break;
			case "paragraph":
				if (event.shiftKey) {
					event.preventDefault();
					editor.insertText("\n");
				} else return next();
				break;
			default:
				event.preventDefault();
				editor.splitBlock();
				this.type = 'paragraph';
				break;
		}
	}

	on_space(event, editor, next) {
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
		this.type = type;

		editor.moveFocusToStartOfNode(startBlock).delete()
	}

	on_backspace(event, editor, next) {
		const { value } = editor;
		const { selection } = value;
		if (selection.isExpanded) return next();
		if (selection.start.offset != 0) return next();

		if (this.type == 'paragraph') return next();

		event.preventDefault();
		this.type = 'paragraph';
	}

	on_delete(event, editor, next) {
		const {value} = editor;
		const {selection} = value;
		const {text} = value.startBlock;

		if (selection.isExpanded) return next();
		if (selection.start.offset != 0) return next();

		if (text == "" && this.type != "paragraph") {
			event.preventDefault();
			this.type = 'paragraph';
		} else return next();
	}

	render_node(props, editor, next) {
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

	render_mark(props, editor, next) {
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