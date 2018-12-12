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

class CompendiumEditor extends React.Component {
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
	if ($("#page_form_editor")[0]) {
		ReactDOM.render(
			e(CompendiumEditor),
			$("#page_form_editor")[0]
		);
	}
}

$(document).ready(init);