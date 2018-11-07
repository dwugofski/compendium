
const regex_tokens = [
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
		match: /\n[^<\n](.|(?<!  )\n[^<\n])*/g,
		trim: /(^\n|(?=.*)  $)/gm,
		mintrim: /(^\n|(?=.*)  $)/gm,
		pre: "<p>",
		post: "</p>"}
];

export class Markup {
	constructor(value) {
		if (value === undefined) this.value = "";
		else this.value = value;
	}

	parse(hide_mkup) {
		const conv = new showdown.Converter({
			noHeaderId: true,
			customizedHeaderId: true,
			ghCompatibleHeaderId: true,
			simplifiedAutoLink: true,
			strikethrough: true,
			simpleLineBreaks: true,
			backslashEscapesHTMLTags: true
		});

		return conv.makeHtml(this.value);
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
}

// Identified [this] but not [[that]]: /([^\[]\[)([^\[\]]*)\]/gm
// Identifies [[this]] but not [that]: /([^\[]\[\[)([^\[\]]*)\]\]/gm
// Identified paragraph: /\n[^<\n](.|(?<!  )\n[^<\n])*/g