
Compendium.Login = {
	login_form : undefined,
	signup_form : undefined,
	screen: undefined,
	navopt: undefined,

	init : function() {
		this.login_form = $("#login_form");
		this.signup_form = $("#signup_form");
		this.screen = $("#login_screen");
		this.navopt = $("#navopt_sign_in");

		$("#login_sign_up").click({action: "signup"}, this.switch_login_screen.bind(this));
		$("#signup_log_in").click({action: "login"}, this.switch_login_screen.bind(this));

		this.navopt.click(this.display_login_screen.bind(this));
		this.screen.click(function(event) {
			if (event.target == this.screen[0]) {
				this.hide_login_screen(event);
			}
		}.bind(this));
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

	submit_login_form : function() {
		var login_data = this.login_form.serializeArray().reduce(function(o, i){ 
			o[i.name] = i.value;
			return o;
		}, {});
		var php_data = {"userident": login_data.username, "password":login_data.password};
		$.post("com/php/login_user.php", php_data, this.handle_php_login.bind(this), "json").fail(this.handle_php_login_error.bind(this));
	},

	handle_php_login : function(data, status, jqxhr) {
		console.log("Login Success");
		console.log(data);
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