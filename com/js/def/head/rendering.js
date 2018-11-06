
var header = undefined;
var navbar = undefined;
var main = undefined;
var sidebar = undefined;
var contnt = undefined;

export function init() {
	header = $("#header");
	navbar = $("#navbar");
	main = $("#main");
	sidebar = $("#sidebar");
	contnt = $("#content");

	$(window).scroll(scrollnav);
	setTimeout(function() {
		window.scrollBy(0, 1);
		scrollnav();
		window.scrollBy(0, -1);
	}, 100);
}


function decodemedia(){
	switch($('#media_ident').css('list-style-type')){
		case 'disc':
			return "print";
		default:
			return "screen";
	}
}

function scrollnav() {	
	var vwh = $(window).height();
	var scrolled = $(window).scrollTop();
	var headh = header.height();
	var navh = navbar.height();
	var ch = contnt.height();
	var cm = parseInt(contnt.css("margin-bottom"), 10);

	if (scrolled > headh) {
		navbar.css("position", "fixed");
		navbar.css("top", 0);
		main.css("margin-top", navh + "px");
		sidebar.css("height", vwh - navh);
	} else {
		navbar.css("position", "relative");
		main.css("margin-top", 0);
		sidebar.css("height", vwh - headh - navh + scrolled);
	}

	if (ch + cm < vwh - contnt.offset().top) contnt.css("min-height", vwh - contnt.offset().top - cm);

	var sidedrop = navh + headh - scrolled;
	if (sidedrop > navh) sidebar.css("top", sidedrop);
	else sidebar.css("top", navh);

	var footer_start = 0;
	$.each($("#books").find("> li"), function(index, value){
		if ($(value).attr("id") != "sidebar_footer") {
			var offset = $(value).position().top + $(value).height() + $(value).css("padding-bottom");
			if (offset > footer_start) footer_start = offset;
		}
	});
	if (footer_start < sidebar.height() - 10) $("#sidebar_footer").css("height", sidebar.height() - footer_start - 10);
	else $("#sidebar_footer").css("height", 0);
}

window.print = function() {
  scrollnav();
  _print();
}

$(document).ready(function() {
	init();
});