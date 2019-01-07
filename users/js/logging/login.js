
import * as Verify from "../../../util/verification.js";

export function init() {
	const login_form_password = $("#login_form_password");

	$("#login_form_submit").click(submit);

	$("#login_form_username").on("input", update);
	login_form_password.on("input", update);

	login_form_password.keydown((e) => {
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

	okay = okay && Verify.field($('#login_form_username'), Verify.username_or_email);
	okay = okay && Verify.field($('#login_form_password'), Verify.password);

	if (okay) {
		enable();
	} else {
		disable();
	}
}

function disable() {
	const login_submit = $("#login_form_submit");
	if (!login_submit.hasClass("disabled")) {
		login_submit.addClass("disabled");
	}
}

function enable() {
	const login_submit = $("#login_form_submit");
	if (login_submit.hasClass("disabled")) {
		login_submit.removeClass("disabled");
	}
}

function submit(e) {
	if (!$("#login_form_submit").hasClass("disabled")) {
		disable();
		const login_data = $("#login_form").serializeArray().reduce(function(o, i){ 
			o[i.name] = i.value;
			return o;
		}, {});
		const php_data = {"userident": login_data.username, "password":login_data.password};
		$.ajax({
			url : "users/ajax/login_user_ajax.php",
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

