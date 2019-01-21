
const e = React.createElement;

export function tag_to_type(tag) {
	const tagname = tag.toLowerCase();
	switch(tagname){
		case "h1":
		case "h2":
		case "h3":
		case "h4":
		case "h5":
		case "h6":
		case "ul":
		case "ol":
		case "li":
		case "img":
			return {type: tagname, obj: "block"};
		case "p":
			return {type: "paragraph", obj: "block"};
		case "strong":
			return {type: "bold", obj: "mark"};
		case "em":
			return {type: "italic", obj: "mark"};
		case "u":
			return {type: "underlined", obj: "mark"};
		case "":
			return {type: "text", obj: "text"};
		default:
			return {type: null, obj: null};
	}
}

export function type_to_tag(type) {
	if (typeof type !== 'string') return null;
	const typename = type.toLowerCase();
	switch(typename) {
		case "h1":
		case "h2":
		case "h3":
		case "h4":
		case "h5":
		case "h6":
		case "ul":
		case "ol":
		case "img":
			return typename;
		case "uli":
		case "oli":
			return "li";
		case "paragraph":
			return "p";
		case "placeholder":
			return "placeholder";
		case "bold":
			return "strong";
		case "italic":
			return "em";
		case "underlined":
			return "u";
		default:
			if (type.substring(0, 3) == "img") return "img";
			return null;
	}
}

export const RULES = [
	{
		deserialize(el, next) {
			var {type, obj} = tag_to_type(el.tagName);
			//console.log(el);
			switch(type) {
				case "li":
					switch(el.parentNode.tagName.toLowerCase()) {
						case "ol":
							type = "oli";
							break;
						case "ul":
							type = "uli";
							break;
					}
					break;
				case "break":
					obj = "text";
					type = "text";
					break;
				case "img":
					type += " " + el.src;
					break;
			}

			if (type) {
				switch(obj) {
					case "block":
					case "mark":
						return {
							object: obj,
							type: type,
							data: {
								className: el.getAttribute('class')
							},
							nodes: next(el.childNodes),
						};
					case "text":
						return {
							object: obj,
							leaves: [{text: el.textContent}]
						}
					default:
						console.error("Tags of type '" + el.tagName + "' are not supported for deserialization!");
						break;
				}
			}
		},

		serialize(obj, children) {
			var tag = type_to_tag(obj.type);
			if (tag && tag != "placeholder") {
				if (tag == "img") return e(tag, {className: obj.data.get("className"), src: obj.type.substring(4)});
				return e(tag, {className: obj.data.get("className")}, children);
			}
			if (tag == "placeholder") return null;
		},
	}
];

export function deseralize_html(html_string) {
	const html = new SlateHtmlSerializer.default({rules: RULES});
	return html.deserialize(html_string);
}

export function serialize_value(value) {
	const html = new SlateHtmlSerializer.default({rules: RULES});
	return html.serialize(value);
}