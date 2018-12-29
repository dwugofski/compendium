
import * as User from "./def/user.js";
import * as Cookies from "./def/cookies.js";
import * as Rendering from "./def/rendering.js";
import * as Edit from "./def/edit.js";

export function init(){
	$("#navopt_home").click(() => {
		location.href="?context=home";
	});

	const val = Edit.Editor.Serialization.deseralize_html(
		"<p><em>hi</em><strong>hello</strong><br/><strong>world!</strong></p><ol><li>i1</li><li>i2</li><li>i3</li></ol>");
	//console.log(val.toJSON());
	//console.log(Edit.Editor.Serialization.seralize_value(val));
}

$(document).ready(init);

export { User, Cookies, Rendering, Edit };