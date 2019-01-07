
import * as User from "./users/user.js";
import * as Cookies from "./util/cookies.js";
import * as Rendering from "./util/rendering.js";
import * as Page from "./pages/page.js";
import * as Editor from "./util/editor/editor.js";

export function init(){
	$("#navopt_home").click(() => {
		location.href="?context=home";
	});
}

$(document).ready(init);

export { User, Cookies, Rendering, Page, Editor };