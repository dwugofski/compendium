
import * as Verify from "./logging/verification.js";

export function init() {
	if ($('#user_delete')[0] === undefined) return;

	$("#navopt_dd_user_delete").click(display_screen);

	$('#user_delete_form_password').on('input', verify_form);
	$("#user_delete_form_submit").click(delete_user);

	$("#user_delete_screen").click(event => {
		if (event.target == $("#user_delete_screen")[0]) {
			hide_screen(event);
		}
	});

	disable_submit();
}

function display_screen(e) {
	$("#user_delete_screen").fadeIn("fast");
}

function hide_screen(e) {
	$("#user_delete_screen").fadeOut("fast");
}

function enable_submit() {
	if ($('#user_delete_form_submit').hasClass("disabled")) $('#user_delete_form_submit').removeClass("disabled");
}

function disable_submit() {
	if (!$('#user_delete_form_submit').hasClass("disabled")) $('#user_delete_form_submit').addClass("disabled");
}

function show_error(str) {
	const error_box = $('#user_delete_error');
	error_box.html(str);
	if (error_box.css("display") == "none") error_box.slideDown();
}

function verify_password(password) {
	const re = /^^([a-zA-Z0-9\[\]<>()\\\/,.?;:'"{}|`~!@#$%^&*\-_=+\ ]{8,100})$/;
	return re.test(String(password));
}

function verify_field(elem, val, eval_f) {
	var okay = true;

	if (val === undefined) val = elem.val();

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

function verify_form() {
	var valid = true;
	valid &= Verify.field($("#user_delete_form_password"), Verify.password);

	if (valid) enable_submit();
	else disable_submit();

	return valid;
}

function delete_user(e) {
	if (verify_form()) {
		disable_submit();
		var data = $("#user_delete_form").serializeArray().reduce(function(o, i){ 
			o[i.name] = i.value;
			return o;
		}, {});
		var php_data = {"password": data.password};
		$.ajax({
			url : "com/php/user/delete_user.php",
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
	enable_login();
}

$(document).ready(() => {
	init();
});