
import * as User from "./def/user.js";
import * as Cookies from "./def/cookies.js";
import * as Rendering from "./def/rendering.js";
import * as Edit from "./def/edit.js";

export function init(){
	$("#navopt_home").click(() => {
		location.href="?context=home";
	});
}

$(document).ready(init);

export { User, Cookies, Rendering, Edit };