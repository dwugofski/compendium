
const text_res = [{
		name: "italic",
		finder: /((?<!\*)\*([^\*](?!\n\n))+\*|(?<!\_)\_([^\_](?!\n\n))+\_)/g,
		trim: /(\*|\_)/
	},{
		name: "bold",
		finder: /((?<!\*)\*\*([^\*](?!\n\n))+\*\*|(?<!\_)\_\_([^\_](?!\n\n))+\_\_)/g,
		trim: /(\*\*|\_\_)/
	},{
		name: "strikethrough",
		finder: /((?<!\*)\*\*([^\*](?!\n\n))+\*\*|(?<!\_)\_\_([^\_](?!\n\n))+\_\_)/g,
		trim: /\~\~/
}];

const node_res = [{
		name: "h1",
		finder: /^([\t\r ]*#(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*#[\t\r ]*)/
	},{
		name: "h2",
		finder: /^([\t\r ]*##(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*##[\t\r ]*)/
	},{
		name: "h3",
		finder: /^([\t\r ]*###(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*###[\t\r ]*)/
	},{
		name: "h4",
		finder: /^([\t\r ]*####(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*####[\t\r ]*)/
	},{
		name: "h5",
		finder: /^([\t\r ]*#####(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*#####[\t\r ]*)/
	},{
		name: "h6",
		finder: /^([\t\r ]*######(?!#)[\S\t\r ]*)/g,
		trim: /^([\t\r ]*######[\t\r ]*)/
}];


export function tokenize(text){
	var tokens = [];

	for (const re of text_res) {
		var start = 0;

		while (start < text.length) {
			const remainder = text.substring(start);
			const loc = remainder.search(re.finder);
			if (loc < 0) break;
			const match_str = remainder.match(re.finder)[0];
			const new_token = {
				type: re.name,
				start: loc+start,
				length: match_str.length
			};
			tokens.push(new_token);
			start += loc + match_str.length;
		}
	}

	return tokens;
}

export function classify(text){
	for (const re of node_res) {
		if (text.search(re.finder) >= 0) return re.name;
	}
	return "paragraph";
}

export function parse(text){
	const converter = new showdown.Converter({
		noHeaderId: true,
		customizedHeaderId: true,
		ghCompatibleHeaderId: true,
		parseImgDimensions: true,
		strikethrough: true,
		tables: true,
		ghCodeBlocks: true,
		simpleLineBreaks: true,
		ghMentions: true,
		ghMentionsLink: "https://compendium.com/u/{u}"
	});
}


/*const regex_tokens = [
	{	name: "header1",
		class: "block",
		match: /^([\t\r ]*#[\S\t\r ]*)/gm,
		trim: /^([\t\r ]*#[\t\r ]*)/,
		mintrim: /^([\t\r ]*)/,
		pre: "<h1>",
		post: "</h1>"},
	{	name: "break",
		class: "block",
		match: /(?<!(  |>|\n))\n[^>\n]/g,
		trim: /\n/,
		mintrim: /\n/,
		pre: "<br />",
		post: ""},
	{	name: "paragraph",
		class: "block",
		match: /\n[^<\n](.|(?<!  )\n[^<\n])*//*g,
		trim: /(^\n|(?=.*)  $)/gm,
		mintrim: /(^\n|(?=.*)  $)/gm,
		pre: "<p>",
		post: "</p>"}
];

export class Markdown {
	constructor(value) {
		if (value === undefined) this.value = "";
		else this.value = value;
	}

	parse(hide_mkup) {
	}
}

function parse_block(token, value, hide_mkup){
	var new_value = "";
	var old_value = value;
	var match_str = "";
	var new_str = "";
	const match_re = token.match;
	var loc = old_value.search(match_re);

	while(loc >= 0) {
		match_str = old_value.match(match_re)[0];
		console.log(match_str);
		if (hide_mkup) {
			new_str = match_str.replace(token.trim, "");
		}
		console.log(new_str);
		new_str = token.pre + new_str + token.post;
		new_value += old_value.substring(0, loc);
		new_value += new_str;
		old_value = old_value.substring(loc+match_str.length);
		loc = old_value.search(match_re);
	}

	return new_value+old_value;
}*/

// Identified [this] but not [[that]]: /([^\[]\[)([^\[\]]*)\]/gm
// Identifies [[this]] but not [that]: /([^\[]\[\[)([^\[\]]*)\]\]/gm
// Identified paragraph: /\n[^<\n](.|(?<!  )\n[^<\n])*/g