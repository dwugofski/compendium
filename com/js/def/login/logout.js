
var logout_dd = undefined;

export function init() {
	if ($('#navopt_user')[0] === undefined) return;

	logout_dd = $("#navopt_dd_logout");

	logout_dd.click(logout);
}

function logout(e) {
	console.log("YO!");
	$.ajax({
		url : "com/php/logout_user.php",
		success : logout_success,
		error : logout_error
	});
}

function logout_success(data, status, jqxhr) {
	location.reload();
}

function logout_error(data, status, jqxhr) {
	console.log(data);
}

$(document).ready(() => {
	init();
});