
import { Screen, Form, Cookies } from "./utils.js"

$(document).ready(() => {
	if ($('#navopt_sign_in')[0]) {
		const login_screen = new Screen('login_screen');
		const login_form = new Form('login_form', '/login');
		login_form.add_field('login_form_username', 'username');
		login_form.add_field('login_form_password', 'password');
		login_form.add_submit('login_form_submit');
		login_form.add_error_box('login_error');
		login_form.bind_success((data, text, jqxhr) => {
			console.log(data);
			console.log(text);
			Cookies.set('compendium_login_token', JSON.parse(data).token);
			location.reload();
		});

		login_form.bind_error((err) => console.log(err.responseText));

		const signup_form = new Form('signup_form', '/signup');
		signup_form.add_field('signup_form_username', 'username');
		signup_form.add_field('signup_form_email', 'email');
		signup_form.add_field('signup_form_password1', 'password');
		signup_form.add_field('signup_form_password2', 'password_2', {password_input: 'signup_form_password1', final: true});
		signup_form.add_submit('signup_form_submit');
		signup_form.add_error_box('login_error');
		signup_form.bind_success((data, text, jqxhr) => {
			console.log(data);
		});

		$('#navopt_sign_in').click(login_screen.show.bind(login_screen));
		$('#login_sign_up').click(e => { $('#login_form').slideUp(); $('#signup_form').slideDown(); });
		$('#signup_log_in').click(e => { $('#signup_form').slideUp(); $('#login_form').slideDown(); });
	}
});

