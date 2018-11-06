

tokens = 

export class Markup {
	constructor(value) {
		if (value === undefined) this.value = "";
		else this.value = value;
	}

	parse(hide_mkup) {
		
	}
}

// Identified [this] but not [[that]]: ([^\[]\[)([^\[\]]*)\]
// Identifies [[this]] but not [that]: ([^\[]\[\[)([^\[\]]*)\]\]