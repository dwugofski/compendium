
import * as Editor from "../util/editor/editor.js";

const e = React.createElement;

const EDITOR_CONTENT_LOC = "EditorContent";

export function init(){
	if (!($("#text_entry")[0])) return;
	$("#page_form_submit").click(submit_form);

	$("#page_form_title").on("input", verify_form);
	$("#page_form_text").on("input", verify_form);
	localStorage.setItem(EDITOR_CONTENT_LOC, "");

	if ($("#page_form_text")[0]) {
		ReactDOM.render(
			e(Editor.Editor, {key: 0, storage: EDITOR_CONTENT_LOC}),
			$("#page_form_text")[0]
		);
	}

	disable_submit();
}

function verify_usersel(usersel) {
	const re = /^[a-fA-F0-9]{24}$/;
	return re.test(String(usersel));
}

function verify_text(text) {
	return true;
}

function verify_title(title) {
	const re = /^[a-zA-Z0-9][\S ]*$/;
	return re.test(String(title));
}

function show_error(msg) {
	$("#page_form_error").text(msg);
	$("#page_form_error").slideDown();
}

function hide_error() {
	$("#page_form_error").slideUp();
}

function disable_submit() {
	const $submit = $("#page_form_submit");
	if (!$submit.hasClass("disabled")) $submit.addClass("disabled");
}

function enable_submit() {
	const $submit = $("#page_form_submit");
	if ($submit.hasClass("disabled")) $submit.removeClass("disabled");
}

function submit_enabled() {
	return !$("#page_form_submit").hasClass("disabled");
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
	valid &= verify_field($("#page_form_user"), undefined, verify_usersel);
	valid &= verify_field($("#page_form_title"), undefined, verify_title);
	//valid &= verify_field($("#page_form_text"), undefined, verify_text);

	if (valid) enable_submit();
	else disable_submit();

	return valid;
}

function submit_form(e){
	if (verify_form() && submit_enabled()) {
		disable_submit();
		var create_data = $("#page_form").serializeArray().reduce(function(o, i){ 
			o[i.name] = i.value;
			return o;
		}, {});
		create_data.text = localStorage.getItem(EDITOR_CONTENT_LOC);
		//console.log(create_data);
		//console.log(create_data.user);
		var php_data = {usersel: create_data.user, title: create_data.title, description: create_data.subtitle, text: create_data.text, parent: create_data.parent};
		$.ajax({
			url : "pages/ajax/create_ajax.php",
			type : "POST",
			data : php_data,
			success : handle_php_created,
			error : handle_php_create_error
		});
	}
}

function handle_php_created(data, status, jqxhr) {
	const sel = JSON.parse(data)
	location.href = getUrlFromJson({context: 'view', page_id: sel});
}

function handle_php_create_error(jqxhr, status, error) {
	show_error(JSON.parse(jqxhr.responseText).error);
	verify_form();
}

$(document).ready(function(){
	init();
});
