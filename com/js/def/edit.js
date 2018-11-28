
//import * as Editor from "./edit/editor.js";
import * as Create from "./edit/create.js";
import * as View from "./edit/view.js";

export function init() {
	if ($("#navopt_dd_create")[0] === undefined) return;

	$("#navopt_dd_create").click(() => {
		window.location.href = "http://www.akatosh.com/compendium/?context=create";
	});
}

export { Create, View };

$(document).ready(() => {
	init();
});