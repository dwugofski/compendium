
//import * as Editor from "./edit/editor.js";
import * as Create from "./edit/create.js";
import * as Edit from "./edit/edit.js";
import * as Editor from "./edit/editor.js";
import * as View from "./edit/view.js";

export function init() {
	if ($("#navopt_dd_create")[0] !== undefined) {
		$("#navopt_dd_create").click(goto_create);
		$("#navopt_create").click(goto_create);
	}

	if ($("#navopt_edit")[0] !== undefined) {
		$("#navopt_edit").click(goto_edit);
	}
}

export function goto_create(event){
	var obj = {context: "create"};
	if ($(event.target).attr("parent") !== undefined) obj.parent_id = $(event.target).attr("parent");
	location.href = getUrlFromJson(obj);
}

export function goto_edit(event){
	var obj = {context: "edit"};
	if ($(event.target).attr("page") !== undefined) obj.page_id = $(event.target).attr("page");
	location.href = getUrlFromJson(obj);
}

export { Create, View, Editor, Edit };

$(document).ready(() => {
	init();
});