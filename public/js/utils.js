
import * as Verify from "./verification.js";
import { Bindable } from "./jclasses.js";

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
	}

	add_submit(submitter_id) {
		$('#'+submitter_id).click(this.submit.bind(this));
		this.submitters.push(submitter_id);
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
				ebox.html(str);
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