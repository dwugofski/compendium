
import * as Login from "./logging/login.js";
import * as Signup from "./logging/signup.js";

export function init(){
	const navopt = $('#navopt_sign_in');
	const screen = $("#login_screen");

	if (navopt[0] === undefined) return;

	$("#login_sign_up").click({action: "signup"}, swap);
	$("#signup_log_in").click({action: "login"}, swap);

	navopt.click(display);
	screen.click(event => {
		if (event.target == screen[0]) {
			hide(event);
		}
	});

	Login.init();
	Signup.init();
}

function swap(event) {
	var action = event.data.action;

	if (action == "signup") {
		$("#login_form").slideUp();
		$("#signup_form").slideDown();
	}
	if (action == "login") {
		$("#login_form").slideDown();
		$("#signup_form").slideUp();
	}
}

function display(event) {
	$('#login_screen').css('display', 'block');
}

function hide(event) {
	$('#login_screen').css('display', 'none');
}

$(document).ready(init);

export {Login, Signup};