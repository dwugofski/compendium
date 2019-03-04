
var header = undefined;
var navbar = undefined;
var main = undefined;
var sidebar = undefined;
var sidebar_list = undefined;
var sidebar_footer = undefined;
var display = undefined;
var content = undefined;
var _sidebar_x_start = null;
var _sidebar_start_width = null;

const MIN_CONTENT_WIDTH = 800;
const MAX_CONTENT_WIDTH = 1200;
const DEFAULT_CONTENT_MARGIN = 75;
const SIDEBAR_MAX_WIDTH = 450;
const SIDEBAR_MIN_WIDTH = 250;

$(document).ready(init);

export function init() {
	header = $("#header");
	navbar = $("#navbar");
	main = $("#main");
	sidebar = $("#sidebar");
	sidebar_list = $("#sidebar_list");
	sidebar_footer = $("#sidebar_footer");
	display = $('#display');
	content = $("#content");

	$(window).scroll(scroll_adjust);
	$(window).resize(resize_main);

	$("#sidebar_adjust").on('mousedown', (mouse_event) => {
		_sidebar_x_start = mouse_event.pageX;
		_sidebar_start_width = sidebar.outerWidth();
		sidebar.addClass("hover");
		$(document).on('mousemove', resize_sidebar);
		$(document).on('mouseup', event => {
			sidebar.removeClass("hover");
			$(document).off('mousemove', null, resize_sidebar);
			_sidebar_x_start = null;
			_sidebar_start_width = null;
		});
	});

	resize_main();

	$('.dropdown-toggle').dropdown();
	$('.dropdown').dropdown();
}

function scroll_adjust() {
	const window_height = $(window).height();
	const window_width = $(window).width();
	const scrolled_distance = $(window).scrollTop();
	const header_height = header.outerHeight();
	const navbar_height = navbar.outerHeight();
	const content_height = content.outerHeight();
	const content_drop = content.offset().top;
	const content_btm_margin = parseInt(content.css("margin-bottom"), 10);

	if (scrolled_distance > header_height) {
		navbar.css("position", "fixed");
		navbar.css("top", 0);
		main.css("margin-top", navbar_height + "px");
		main.css("height", window_height - navbar_height);
	} else {
		navbar.css("position", "relative");
		main.css("margin-top", 0);
		main.css("height", window_height - header_height - navbar_height + scrolled_distance);
	}

	display.css("width", (sidebar.outerWidth() + content.outerWidth()));

	const disp_width = display.outerWidth();

	display.css("margin-left", (window_width - disp_width) / 2);
	display.css("margin-right", (window_width - disp_width) / 2);
	//console.log(disp_width);
	//console.log(window_width);

	const sidebar_height = sidebar.outerHeight();
	if (sidebar_height > content.height()) content.css('height', sidebar_height);

	const disp_height = display.outerHeight();

	return;

	if (content_height + DEFAULT_CONTENT_MARGIN < window_height - content_drop) {
		content.css("margin-bottom", window_height - content_drop - content_height);
	}
	if (content_height + DEFAULT_CONTENT_MARGIN > window_height - content_drop) {
		content.css("margin-bottom", Math.max(window_height - content_drop - content_height, DEFAULT_CONTENT_MARGIN));
	}

	const sidebar_drop = navbar_height + header_height - scrolled_distance;
	if (sidebar_drop > navbar_height) sidebar.css("top", sidebar_drop);
	else sidebar.css("top", navbar_height);

	if (sidebar.css("display") && sidebar.css("display") != "none") {
		const sidebar_list_height = sidebar_list.outerHeight();
		const sidebar_height = sidebar.outerHeight()
		if (sidebar_list_height < sidebar_height) {
			const footer_relative_drop = sidebar_footer.position().top;
			const footer_height = sidebar_footer.outerHeight();

			const new_height = footer_height + ( sidebar_height - sidebar_list_height );
		} else sidebar_footer.css("height", "0");
	}
}

function resize_display() {
	const display_width = display.width();
	const desired_width = display_width - 2 * DEFAULT_CONTENT_MARGIN;
	var margin_size = DEFAULT_CONTENT_MARGIN;

	if ( desired_width < MIN_CONTENT_WIDTH ) margin_size = (display_width - MIN_CONTENT_WIDTH) / 2;
	else if ( desired_width > MAX_CONTENT_WIDTH ) margin_size = (display_width - MAX_CONTENT_WIDTH) / 2;
	margin_size = Math.max(margin_size, 0);

	$("#content").css("margin-left", margin_size);
	$("#content").css("margin-right", margin_size);

	scroll_adjust();
}

function resize_main() {
	return scroll_adjust();
	const main_width = main.width();
	const sidebar_width = (sidebar.css("display") && sidebar.css("display") != "none") ? sidebar_width = sidebar.width() : 0;

	display.css("width", main_width - sidebar_width);
	resize_display();
}

function resize_sidebar(mouse_event) {
	mouse_event.preventDefault();
	const dx = (_sidebar_x_start - mouse_event.pageX);
	var next_width = _sidebar_start_width - dx;

	if (next_width < SIDEBAR_MIN_WIDTH) next_width = SIDEBAR_MIN_WIDTH;
	else if (next_width > SIDEBAR_MAX_WIDTH) next_width = SIDEBAR_MAX_WIDTH;

	sidebar.css("width", next_width);
	resize_main();
}