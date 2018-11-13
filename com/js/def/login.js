
var login_form = undefined;
var signup_form = undefined;
var screen = undefined;
var navopt = undefined;

var switch_to_signup = undefined;
var login_submit = undefined;
var login_form_username = undefined;
var login_form_password = undefined;

var switch_to_login = undefined;
var signup_submit = undefined;
var signup_form_username = undefined;
var signup_form_email = undefined;
var signup_form_password1 = undefined;
var signup_form_password2 = undefined;

var error_box = undefined;

$(document).ready(function() {
    $(".dropdown-toggle").dropdown();
});

export function init() {
	$('.dropdown').dropdown();
	if ($('#navopt_sign_in')[0] === undefined) return;

	login_form = $("#login_form");
	signup_form = $("#signup_form");
	screen = $("#login_screen");
	navopt = $("#navopt_sign_in");

	switch_to_signup = $("#login_sign_up");
	login_submit = $("#login_form_submit");
	login_form_username = $("#login_form_username");
	login_form_password = $("#login_form_password");

	switch_to_login = $("#signup_log_in");
	signup_submit = $("#signup_form_submit");
	signup_form_username = $("#signup_form_username");
	signup_form_email = $("#signup_form_email");
	signup_form_password1 = $("#signup_form_password1");
	signup_form_password2 = $("#signup_form_password2");

	error_box = $("#login_error");

	switch_to_signup.click({action: "signup"}, switch_login_screen);
	switch_to_login.click({action: "login"}, switch_login_screen);

	login_submit.click(submit_login_form);

	login_form_username.on("input", update_login_field);
	login_form_password.on("input", update_login_field);

	signup_form_username.on("input", update_signup_field);
	signup_form_email.on("input", update_signup_field);
	signup_form_password1.on("input", update_signup_field);
	signup_form_password2.on("input", update_signup_field);

	signup_submit.click(submit_signup_form);

	disable_login();
	disable_signup();

	navopt.click(display_login_screen);
	screen.click(event => {
		if (event.target == screen[0]) {
			hide_login_screen(event);
		}
	});
}

function verify_email(email) {
	const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}

function verify_password(password) {
	const re = /^^([a-zA-Z0-9\[\]<>()\\\/,.?;:'"{}|`~!@#$%^&*\-_=+\ ]{8,100})$/;
	return re.test(String(password));
}

function verify_password_match(password) {
	return password == signup_form_password1.val();
}

function verify_username(username) {
	const re = /([a-zA-Z])([\w]{2,24})$/;
	return re.test(String(username));
}

function verify_username_or_email(username) {
	return verify_username(username) || verify_email(username);
}

function show_error(str) {
	if (error_box.css("display") == "none") error_box.slideDown();
	error_box.html(str);
}

function verify_field(elem, val, eval_f) {
	var okay = true;

	if (val && 0 !== val.length) {
		if (!eval_f(val)) {
			okay = false;
			if (!elem.hasClass("invalid")) elem.addClass("invalid");
		} else if (elem.hasClass("invalid")) elem.removeClass("invalid");
	} else {
		okay = false;
		if (elem.hasClass("invalid")) elem.removeClass("invalid");
	}

	return okay;
}

function update_login_field(e) {
	var okay = true;

	okay = okay && verify_field(login_form_username, login_form_username.val(), verify_username_or_email);
	okay = okay && verify_field(login_form_password, login_form_password.val(), verify_password);

	if (okay) {
		enable_login();
	} else {
		disable_login();
	}
}

function update_signup_field(e) {
	var okay = true;

	okay = okay && verify_field(signup_form_username, signup_form_username.val(), verify_username);
	okay = okay && verify_field(signup_form_email, signup_form_email.val(), verify_email);
	okay = okay && verify_field(signup_form_password1, signup_form_password1.val(), verify_password);
	okay = okay && verify_field(signup_form_password2, signup_form_password2.val(), verify_password_match);

	if (okay) enable_signup();
	else disable_signup();
}

function disable_login() {
	if (!login_submit.hasClass("disabled")) {
		login_submit.addClass("disabled");
	}
	login_submit.disabled = true;
}

function enable_login() {
	if (login_submit.hasClass("disabled")) {
		login_submit.removeClass("disabled");
	}
	login_submit.disabled = false;
}

function disable_signup() {
	if (!signup_submit.hasClass("disabled")) {
		signup_submit.addClass("disabled");
	}
	signup_submit.disabled = true;
}

function enable_signup() {
	if (signup_submit.hasClass("disabled")) {
		signup_submit.removeClass("disabled");
	}
	signup_submit.disabled = false;
}

function display_login_screen(event) {
	screen.fadeIn("fast");
}

function hide_login_screen(event) {
	screen.fadeOut("fast");
}

function switch_login_screen(event) {
	var action = event.data.action;

	if (action == "signup") {
		login_form.slideUp();
		signup_form.slideDown();
	}
	if (action == "login") {
		login_form.slideDown();
		signup_form.slideUp();
	}
}

function submit_login_form(e) {
	if (!login_submit.disabled) {
		var login_data = login_form.serializeArray().reduce(function(o, i){ 
			o[i.name] = i.value;
			return o;
		}, {});
		var php_data = {"userident": login_data.username, "password":login_data.password};
		$.ajax({
			url : "com/php/login_user.php",
			type : "POST",
			data : php_data,
			success : handle_php_login,
			error : handle_php_login_error
		});
	}
}

function handle_php_login(data, status, jqxhr) {
	console.log("Login Success");
	console.log(JSON.parse(data));
}

function handle_php_login_error(data, status, jqxhr) {
	console.log("Login Error");
	console.log(data);
}

function submit_signup_form(e) {
	console.log("okay");
	if (!signup_submit.disabled) {
		var signup_data = signup_form.serializeArray().reduce((o, i) => { 
			o[i.name] = i.value;
			return o;
		}, {});
		var php_data = {"username": signup_data.username, "email": signup_data.email, "password": signup_data.password1};
		$.ajax({
			url : "com/php/signup_user.php",
			type : "POST",
			data : php_data,
			success : handle_php_signup,
			error : handle_php_signup_error
		});
	}
}

function handle_php_signup(data, status, jqxhr) {
	console.log("Signup Success");
	console.log(data);
	console.log(JSON.parse(data));
}

function handle_php_signup_error(data, status, jqxhr) {
	console.log("Signup Error");
	console.log(data);
}

$(document).ready(function(){
	init();
});
