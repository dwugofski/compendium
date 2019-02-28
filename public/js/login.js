
import { Screen, Form } from "./utils.js"

$(document).ready(() => {
	if ($('#navopt_sign_in')[0]) {
		const login_screen = new Screen('login_screen');
		const login_form = new Form('login_form', '');
		login_form.add_field('login_form_username', 'username');
		login_form.add_field('login_form_password', 'password');
		login_form.add_submit('login_form_submit');
		login_form.add_error_box('login_error');

		const signup_form = new Form('signup_form', '');
		signup_form.add_field('signup_form_username', 'username');
		signup_form.add_field('signup_form_email', 'email');
		signup_form.add_field('signup_form_password1', 'password');
		signup_form.add_field('signup_form_password2', 'password_2', {password_input: 'signup_form_password1', final: true});
		signup_form.add_submit('signup_form_submit');
		signup_form.add_error_box('login_error');

		$('#navopt_sign_in').click(login_screen.show.bind(login_screen));
	}
});

