
//import * as Editor from "./edit/editor.js";
import * as Create from "./edit/create.js";
import * as Editor from "./edit/editor.js";
import * as View from "./edit/view.js";

export function init() {
	if ($("#navopt_dd_create")[0] === undefined) return;

	$("#navopt_dd_create").click(goto_create);
	$("#navopt_create").click(goto_create);
}

export function goto_create(event){
	var obj = {context: "create"};
	if ($(event.target).attr("parent") !== undefined) obj.parent_id = $(event.target).attr("parent");
	location.href = getUrlFromJson(obj);
}

export { Create, View, Editor };

$(document).ready(() => {
	init();
});