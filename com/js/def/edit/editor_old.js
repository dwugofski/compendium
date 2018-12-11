
import * as Markdown from "./markdown.js";

var preview_mode = "edit";

const e = React.createElement;

const initialValue = Slate.Value.fromJSON({
	document: {
		nodes: [
			{	object: 'block',
				type: 'paragraph',
				nodes: [{
						object: 'text',
						leaves: [{text: 'Begin writing here...'}]
				}]
			}
		]
	}
});

export class CompEditor extends React.Component {
	constructor() {
		super();
		this.state = {value: initialValue};
	}

	onChange({value}) {
		this.setState({value});
	}

	onKeyDown(event, editor, next) {
		switch(event.key) {
			case 'Enter':
				return this.onEnter(event, editor, next);
			default:
				return next();
		}
	}

	onEnter(event, editor, next) {
		const {value} = editor;
		const {selection} = value;
		const start_block = value.startBlock;
		const {start, end, expanded} = selection;
		if (expanded) return next();

		if (start.offset == 0 && start_block.text.length == 0) return next();
		if (end.offset != start_block.text.length) return next();

		switch(start_block.type){
			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			default:
				event.preventDefault();
				editor.splitBlock().setBlocks('paragraph');
				break;
			case "paragraph":
				if (event.shiftKey && false) {
					event.preventDefault();
					editor.insertInline({object: 'inline', type: 'break', text: ""});
				} else return next();
		}
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
				return e(node.type, attributes, children);
			case "paragraph":
				return e("p", attributes, children);
			case "break":
				return e('span', {...attributes, style: {display: 'block'}}, children);
			default:
				return next();
		}
	}

	renderMark(props, editor, next) {
		const { children, mark, attributes } = props;

		switch(mark.type) {
			case "italic":
				return e("em", attributes, children);
			case "bold":
				return e("strong", attributes, children);
			default:
				return next();
		}
	}

	decorateNode(node, editor, next) {
		const others = next() || [];
		if (node.object != 'block') return others;

		if (node.getBlocks().size != 0) return others;
		else {
			const new_type = Markdown.classify(node.text)
			editor.setNodeByKey(node.key, new_type);
		}

		const string = node.text;
		const tokens = Markdown.tokenize(string);
		const decorations = [];

		for (const token of tokens) {
			const texts = node.getTexts().toArray();
			var start_text = texts.shift();
			var start_offset = 0;
			var start = 0;

			while (start_offset + start < token.start) {
				if (token.start >= start_offset + start_text.text.length) {
					start_offset += start_text.text.length;
					start_text = texts.shift();
				} else {
					start = token.start - start_offset;
				}
			}

			var end_text = start_text;
			var end_offset = start_offset + end_text.text.length;
			while (end_offset < token.start + token.length) {
				end_text = texts.shift();
				end_offset += end_text.text.length;
			}

			const dec = {
				anchor: {
					key: end_text.key,
					offset: token.start
				},
				focus: {
					key: end_text.key,
					offset: token.start + token.length
				},
				mark: {
					type: token.type
				}
			};

			decorations.push(dec);
		}

		return [...others, ...decorations];
	}

	render() {
		return e(
			SlateReact.Editor,
			{	value: this.state.value,
				onChange: this.onChange.bind(this),
				onKeyDown: this.onKeyDown.bind(this),
				renderMark: this.renderMark.bind(this),
				renderNode: this.renderNode.bind(this),
				decorateNode: this.decorateNode.bind(this),
				className: "mkdn_editor"
			});
	}
}

export function init() {
	$("#page_form_switch_sub span").click(toggle_preview);
}

function toggle_preview() {
	if (preview_mode == "edit") {
		preview_mode = "preview";
		hide_edit(show_preview);
	} else {
		preview_mode = "edit";
		hide_preview(show_edit);
	}
}

function hide_edit(callback) {
	$("#page_form_text").hide(100, callback);
}

function hide_preview(callback) {
	$("#page_form_preview").hide(100, callback);
}

function show_preview(callback) {
	$("#page_form_preview").html(Markdown.parse($("#page_form_text").val()));
	$("#page_form_switch_sub span").text("Edit");
	$("#page_form_preview").show(100, callback);
}

function show_edit(callback) {
	$("#page_form_switch_sub span").text("Preview");
	$("#page_form_text").show(100, callback);
}

/*
ReactDOM.render(
	e(CompEditor),
	$("#page_form_text")[0]
);*/

$(document).ready(function(){
	init();
});
