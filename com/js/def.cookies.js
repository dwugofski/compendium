
Compendium.Cookies = {

	set : function(cname, cvalue, exdays=0, exhours=0, exminutes=0, exseconds=0) {
		var expires = "expires=Thu, 01 Jan 1970 00:00:00 UTC"
		if (cvalue !== undefined) {
			var d = new Date();
			exhours = exhours + exdays*24;
			exminutes = exminutes + exhours*60;
			exseconds = exseconds + exminutes*60;
			d.setTime(d.getTime() + (exseconds*1000));
			expires = "expires=" + d.toUTCString();
		}
		document.cookie = cname + "=" + JSON.stringify(cvalue) + ";" + expires + ";path=/";
	},

	get : function(cname) {
		var cookies_str = decodeURIComponent(document.cookie);
		var name = cname + "=";
		var cookies_arr = cookies_str.split(";");
		for (var i=0; i < cookies_arr.length; i++) {
			var cookie = cookies_arr[i];
			while(cookie.charAt(0) == ' ') {
				cookie = cookie.substring(1);
			}
			if (cookie.indexOf(name) == 0) {
				return JSON.parse(cookie.substring(name.length, cookie.length));
			}
		}
		return undefined;
	},

	delete : function(cname) {
		this.set(cname, undefined);
	},

	list : function() {
		var list = Array();
		var cookies_str = decodeURIComponent(document.cookie);
		var cookies_arr = cookies_str.split(";");
		for (var i=0; i < cookies_arr.length; i++) {
			var cookie = cookies_arr[i];
			var name_end = cookie.indexOf("=");
			if (name_end < 0) continue;
			var name = cookie.substring(0, name_end);
			var value = JSON.parse(cookie.substring(name_end+1));
			while(name.charAt(0) == ' ') {
				name = name.substring(1);
			}
			list.push({"name" : name, "value" : value});
		}
		return list;
	},

	namespace : "Compendium.Cookies"
};