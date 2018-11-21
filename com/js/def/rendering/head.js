
var header = undefined;
var navbar = undefined;
var main = undefined;
var sidebar = undefined;
var contnt = undefined;

const min_content_width = 1000;
const max_content_width = 1400;
const def_content_margin = 75;

export function init() {
	header = $("#header");
	navbar = $("#navbar");
	main = $("#main");
	sidebar = $("#sidebar");
	contnt = $("#content");

	$(window).scroll(scrollnav);
	$(window).resize(main_resize);

	setTimeout(function() {
		window.scrollBy(0, 1);
		scrollnav();
		window.scrollBy(0, -1);
		main_resize();
	}, 100);

	$('.dropdown-toggle').dropdown();
	$('.dropdown').dropdown();
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
	const vwh = $(window).height();
	const scrolled = $(window).scrollTop();
	const headh = header.outerHeight();
	const navh = navbar.outerHeight();
	const ch = contnt.outerHeight();
	const cm = parseInt(contnt.css("margin-bottom"), 10);

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

	if (ch + def_content_margin < vwh - contnt.offset().top) contnt.css("margin-bottom", vwh - contnt.offset().top - ch);
	if (ch + def_content_margin > vwh - contnt.offset().top) contnt.css("margin-bottom", Math.max( vwh - contnt.offset().top - ch, def_content_margin ));

	var sidedrop = navh + headh - scrolled;
	if (sidedrop > navh) sidebar.css("top", sidedrop);
	else sidebar.css("top", navh);

	if ($("#sidebar").css("display") != "none"){
		var footer_start = 0;
		$.each($("#books").find("> li"), function(index, value){
			if ($(value).attr("id") != "sidebar_footer") {
				var offset = $(value).position().top + $(value).outerHeight() + parseInt($(value).css("margin-bottom"));
				if (offset > footer_start) footer_start = offset;
			}
		});
		if (footer_start < sidebar.outerHeight()) $("#sidebar_footer").css("height", sidebar.outerHeight() - footer_start);
		else $("#sidebar_footer").css("height", 0);
	}
}

function main_resize() {
	const mw = $("#main").width();
	var sw = 0;

	if ( $("#sidebar").css("display") != "none") {
		sw = $("#sidebar").width();
	}

	$("#display").css("width", mw - sw);
	disp_resize();
}

function disp_resize() {
	const dw = $("#display").width();

	if (dw - 2*def_content_margin <= min_content_width) {
		$("#content").css("margin-left", Math.max((dw - min_content_width)/2, 0));
		$("#content").css("margin-right", Math.max((dw - min_content_width)/2, 0));
	}
	if (dw - 2*def_content_margin > max_content_width) {
		$("#content").css("margin-left", Math.max((dw - max_content_width)/2, 0));
		$("#content").css("margin-right", Math.max((dw - max_content_width)/2, 0));
	} else if (dw - 2*def_content_margin > min_content_width) {
		$("#content").css("margin-left", def_content_margin);
		$("#content").css("margin-right", def_content_margin);
	}

	scrollnav();
}

window.print = function() {
  scrollnav();
  _print();
}

$(document).ready(() => {
	init();
});