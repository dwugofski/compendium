
Compendium.Head.Rendering = {
	header : undefined,
	navbar : undefined,
	main : undefined,
	sidebar : undefined,
	content : undefined,

	init : function() {
		this.header = $("#header");
		this.navbar = $("#navbar");
		this.main = $("#main");
		this.sidebar = $("#sidebar");
		this.contnt = $("#content");
	},


	decodemedia : function(){
		switch($('#media_ident').css('list-style-type')){
			case 'disc':
				return "print";
			default:
				return "screen";
		}
	},

	scrollnav : function() {
		var vwh = $(window).height();
		var scrolled = $(window).scrollTop();
		var headh = this.header.height();
		var navh = this.navbar.height();
		var ch = this.contnt.height()
		var cm = parseInt(this.contnt.css("margin-bottom"), 10);

		console.log(cm);

		if (scrolled > headh) {
			this.navbar.css("position", "fixed");
			this.navbar.css("top", 0);
			this.main.css("margin-top", navh + "px");
			this.sidebar.css("height", vwh - navh);
		} else {
			this.navbar.css("position", "relative");
			this.main.css("margin-top", 0);
			this.sidebar.css("height", vwh - headh - navh + scrolled);
		}

		if (ch + cm < vwh - this.contnt.offset().top) this.contnt.css("min-height", vwh - this.contnt.offset().top - cm);

		var sidedrop = navh + headh - scrolled;
		if (sidedrop > navh) this.sidebar.css("top", sidedrop);
		else this.sidebar.css("top", navh);

		var footer_start = 0;
		$.each($("#books").find("> li"), function(index, value){
			if ($(value).attr("id") != "sidebar_footer") {
				var offset = $(value).position().top + $(value).height() + $(value).css("padding-bottom");
				if (offset > footer_start) footer_start = offset;
			}
		});
		if (footer_start < this.sidebar.height() - 10) $("#sidebar_footer").css("height", this.sidebar.height() - footer_start - 10);
		else $("#sidebar_footer").css("height", 0);
	},

	namespace : "Compendium.Head.Rendering"
}

window.print = function() {
  Compendium.Head.Rendering.scrollnav();
  // do stuff
  _print();
}

$(document).ready(function() {
	Compendium.Head.Rendering.init();	
	$(window).scroll(Compendium.Head.Rendering.scrollnav.bind(Compendium.Head.Rendering));
	setTimeout(function() {
		window.scrollBy(0, 1);
		Compendium.Head.Rendering.scrollnav();
		window.scrollBy(0, -1);
	}, 100);
});