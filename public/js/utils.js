
import * as Verify from "./verification.js";
import { Bindable } from "./jclasses.js";

export class Cookies {
	static set(name, value, expiry=30) {
		var d = new Date();
		var expiration_ms = 0;
		if (typeof expiry == "obejct") {
			if (expiry.years) expiration_ms += expiry.years * 365 * 24 * 60 * 60 * 1000;
			if (expiry.months) expiration_ms += expiry.months * 30 * 24 * 60 * 60 * 1000;
			if (expiry.days) expiration_ms += expiry.days * 24 * 60 * 60 * 1000;
			if (expiry.hours) expiration_ms += expiry.hours * 60 * 60 * 1000;
			if (expiry.minutes) expiration_ms += expiry.minutes * 60 * 1000;
			if (expiry.seconds) expiration_ms += expiry.seconds * 1000;
		} else expiration_ms = expiry * 24 * 60 * 60 * 1000;

		const expires = d.toUTCString();
		document.cookie = name + "=" + JSON.stringify(value) + ";" + expires + ";path=/";
	}

	static get(name) {
		const search = name + "=";
		const cookies = decodeURIComponent(document.cookie);
		const cookie_arr = cookies.split(';');
		for (var i = 0; i < cookie_arr.length; i++) {
			let cookie = cookie_arr[i];
			while (cookie.charAt(0) == ' ') {
				cookie = cookie.substring(1);
			}
			if (cookie.indexOf(search) == 0) {
				return JSON.parse(cookie.substring(name.length, cookie.length));
			}
		}
		return undefined;
	}

	static delete(name) {
		this.set(name, null, -1);
	}
}

export class Screen {
	constructor(screen_id) {
		const screen = $('#'+screen_id);
		this.id = screen_id;

		screen.click((event => {
			if (event.target == screen[0]) {
				this.hide(event);
			}
		}).bind(this));
	}

	show(event) {
		$('#'+this.id).fadeIn("fast");
	}

	hide(event) {
		$('#'+this.id).fadeOut("fast");
	}

	add_trigger(trigger_id) {
		$('#'+trigger_id).click(this.show.bind(this));
	}
}

export class Form extends Bindable(Object) {
	constructor(form_id, url) {
		super();
		this.id = form_id;
		this.url = url;
		this.field_pairs = [];
		this.error_boxes = [];
		this.submitters = [];
		this.enabled = false;

		this._create_binding("error");
		this._create_binding("success");

		this.bind_error(this.show_error.bind(this));
	}

	update_data(e) {
		var okay = true;

		for (var i = 0; i < this.field_pairs.length; i += 1) {
			okay = okay && Verify.field($('#'+this.field_pairs[i].field_id), this.field_pairs[i].verification_callback);
		}

		if (okay) this.enable();
		else this.disable();
	}

	enable() {
		this.enabled = true;
		for (var i = 0; i < this.submitters.length; i += 1) {
			var submitter = $('#' + this.submitters[i]);

			if (submitter.hasClass('disabled')) submitter.removeClass('disabled');
		}
	}

	disable() {
		this.enabled = false;
		for (var i = 0; i < this.submitters.length; i += 1) {
			var submitter = $('#' + this.submitters[i]);

			if (!submitter.hasClass('disabled')) submitter.addClass('disabled');
		}
	}

	add_field(field_id, field_type, details={}) {
		$('#'+field_id).on('input', this.update_data.bind(this));
		var verification_callback = (field_data) => { return true };
		switch(field_type) {
			case 'username':
				verification_callback = Verify.username;
				break;
			case 'email':
				verification_callback = Verify.email;
				break;
			case 'password':
				verification_callback = Verify.password;
				break;
			case 'password_2':
				verification_callback = field_data => {
					return Verify.password_match($('#'+details.password_input).val(), field_data);
				};
				break;
		}
		this.field_pairs.push({field_id, verification_callback});

		if (details.final) {
			$('#'+field_id).keydown((e => {
				const key = (e.keyCode ? e.keyCode : e.which);
				if (key == '13') this.submit();
			}).bind(this));
		}

		this.update_data();
	}

	add_submit(submitter_id) {
		$('#'+submitter_id).click(this.submit.bind(this));
		this.submitters.push(submitter_id);

		this.update_data();
	}

	add_error_box(error_box_id) {
		this.error_boxes.push(error_box_id);
	}

	show_error(str) {
		for (var i = 0; i < this.error_boxes.length; i += 1) {
			var ebox = $('#'+this.error_boxes[i]);

			if (!str) ebox.slideUp();
			else {
				ebox.slideDown();
				ebox.html(str.responseText);
			}
		}
	}

	submit(e) {
		if (this.enabled) {
			this.disable();
			this.show_error('');
			const form_data = $('#'+this.id).serializeArray().reduce((o, i) => {
				o[i.name] = i.value;
				return o;
			}, {});
			console.log(form_data);
			$.ajax({
				url: this.url,
				type: "POST",
				data: form_data,
				success: this.on_success.bind(this),
				error: this.on_error.bind(this)
			});
		}
	}
}