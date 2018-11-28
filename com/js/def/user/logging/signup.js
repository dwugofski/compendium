
import * as Verify from "./verification.js";

export function init() {
	const signup_form_password2 = $("#signup_form_password2");

	$("#signup_form_submit").click(submit);

	$("#signup_form_username").on("input", update);
	$("#signup_form_email").on("input", update);
	$("#signup_form_password1").on("input", update);
	signup_form_password2.on("input", update);

	signup_form_password2.keydown((e) => {
		const key = (e.keyCode ? e.keyCode : e.which);
		if (key == '13') submit();
	});

	disable();
}

function show_error(str) {
	const error_box = $("#login_error");

	if (error_box.css("display") == "none") error_box.slideDown();
	error_box.html(str);
}

function update(e) {
	var okay = true;

	okay = Verify.field($('#signup_form_username'), Verify.username);
	okay = Verify.field($('#signup_form_email'), Verify.email) && okay;
	okay = Verify.field($('#signup_form_password1'), Verify.password) && okay;
	okay = Verify.field($('#signup_form_password2'), (password2) => { return Verify.password_match($('#signup_form_password1').val(), password2);}) && okay;

	if (okay) enable();
	else disable();
}

function disable() {
	const signup_submit = $('#signup_form_submit');
	if (!signup_submit.hasClass("disabled")) {
		signup_submit.addClass("disabled");
	}
}

function enable() {
	const signup_submit = $('#signup_form_submit');
	if (signup_submit.hasClass("disabled")) {
		signup_submit.removeClass("disabled");
	}
}

function submit(e) {
	if (!$("#signup_form_submit").hasClass("disabled")) {
		disable();
		const signup_data = $("#signup_form").serializeArray().reduce((o, i) => { 
			o[i.name] = i.value;
			return o;
		}, {});
		const php_data = {"username": signup_data.username, "email": signup_data.email, "password": signup_data.password1};
		$.ajax({
			url : "com/php/user/signup_user.php",
			type : "POST",
			data : php_data,
			success : handle_success,
			error : handle_error
		});
	}
}

function handle_success(data, status, jqxhr) {
	location.reload();
}

function handle_error(jqxhr, status, error) {
	show_error(JSON.parse(jqxhr.responseText).error);
	update();
}

