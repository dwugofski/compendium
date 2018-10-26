
Compendium.Login = {
	login_form : undefined,
	signup_form : undefined,
	screen: undefined,
	navopt: undefined,

	switch_to_signup : undefined,
	login_submit : undefined,
	login_form_username : undefined,
	login_form_password : undefined,

	switch_to_login : undefined,
	signup_submit : undefined,

	error_box : undefined,

	init : function() {
		this.login_form = $("#login_form");
		this.signup_form = $("#signup_form");
		this.screen = $("#login_screen");
		this.navopt = $("#navopt_sign_in");

		this.switch_to_signup = $("#login_sign_up");
		this.login_submit = $("#login_form_submit");
		this.login_form_username = $("#login_form_username");
		this.login_form_password = $("#login_form_password");

		this.switch_to_login = $("#signup_log_in");
		this.signup_submit = $("#signup_form_submit");

		this.error_box = $("#login_error");

		this.switch_to_signup.click({action: "signup"}, this.switch_login_screen.bind(this));
		this.switch_to_login.click({action: "login"}, this.switch_login_screen.bind(this));

		this.login_submit.click(this.submit_login_form.bind(this));

		this.login_form_username.on("input", this.update_login_field.bind(this));
		this.login_form_password.on("input", this.update_login_field.bind(this));

		this.disable_login();
		this.disable_signup();

		this.navopt.click(this.display_login_screen.bind(this));
		this.screen.click(function(event) {
			if (event.target == this.screen[0]) {
				this.hide_login_screen(event);
			}
		}.bind(this));
	},

	verify_email : function(email) {
		var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(String(email).toLowerCase());
	},

	verify_password : function(password) {
		var re = /^^([a-zA-Z0-9\[\]<>()\\\/,.?;:'"{}|`~!@#$%^&*\-_=+\ ]{8,100})$/;
		return re.test(String(password));
	},

	verify_username : function(username) {
		var re = /([a-zA-Z])([\w]{2,24})$/;
		return re.test(String(username));
	},

	show_error : function(str) {
		if (this.error_box.css("display") == "none") this.error_box.slideDown();
		this.error_box.html(str);
	},

	update_login_field : function(e) {
		var okay = true;

		console.log("Changed");

		okay = okay && (this.login_form_username.val() && 0 !== this.login_form_username.val().length);
		okay = okay && this.verify_username(this.login_form_username.val());
		okay = okay && (this.login_form_password.val() && 0 !== this.login_form_password.val().length);
		okay = okay && this.verify_password(this.login_form_password.val());
		if (okay) {
			console.log("password okay");
		}

		if (okay) {
			this.enable_login();
		} else {
			this.disable_login();
		}
	},

	disable_login : function() {
		if (!this.login_submit.hasClass("disabled")) {
			this.login_submit.addClass("disabled");
		}
		this.login_submit.disabled = true;
	},

	enable_login : function() {
		if (this.login_submit.hasClass("disabled")) {
			this.login_submit.removeClass("disabled");
		}
		this.login_submit.disabled = false;
	},

	disable_signup : function() {
		if (!this.signup_submit.hasClass("disabled")) {
			this.signup_submit.addClass("disabled");
		}
		this.signup_submit.disabled = true;
	},

	enable_signup : function() {
		if (this.signup_submit.hasClass("disabled")) {
			this.signup_submit.removeClass("disabled");
		}
		this.signup_submit.disabled = false;
	},

	display_login_screen : function(event) {
		this.screen.fadeIn("fast");
	},

	hide_login_screen : function(event) {
		this.screen.fadeOut("fast");
	},

	switch_login_screen : function(event) {
		var action = event.data.action;

		if (action == "signup") {
			this.login_form.slideUp();
			this.signup_form.slideDown();
		}
		if (action == "login") {
			this.login_form.slideDown();
			this.signup_form.slideUp();
		}
	},

	submit_login_form : function(e) {
		if (!this.login_submit.disabled) {
			var login_data = this.login_form.serializeArray().reduce(function(o, i){ 
				o[i.name] = i.value;
				return o;
			}, {});
			var php_data = {"userident": login_data.username, "password":login_data.password};
			$.ajax({
				url : "com/php/login_user.php",
				type : "POST",
				data : php_data,
				success : this.handle_php_login.bind(this),
				error : this.handle_php_login_error.bind(this)
			});
		}
	},

	handle_php_login : function(data, status, jqxhr) {
		console.log("Login Success");
		console.log(JSON.parse(data));
	},

	handle_php_login_error : function(data, status, jqxhr) {
		console.log("Login Error");
		console.log(data);
	},

	namespace : "Compendium.Login"
};

$(document).ready(function(){
	Compendium.Login.init();
});