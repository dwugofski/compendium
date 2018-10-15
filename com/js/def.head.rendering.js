
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
	var headh = 71;//$("#header").height();
	var navh = $("#navbar").height();

	if (scrolled > headh) {
		$("#navbar").css("position", "fixed");
		$("#navbar").css("top", 0);
		$("#main").css("margin-top", navh + "px");
		$("#sidebar").css("height", vwh - navh);
	} else {
		$("#navbar").css("position", "relative");
		$("#main").css("margin-top", 0);
		$("#sidebar").css("height", vwh - headh - navh + scrolled);
	}

	var sidedrop = navh + headh - scrolled;
	if (sidedrop > navh) $("#sidebar").css("top", sidedrop);
	else $("#sidebar").css("top", navh);

	var footer_start = 0;
	$.each($("#books").find("> li"), function(index, value){
		if ($(value).attr("id") != "sidebar_footer") {
			var offset = $(value).position().top + $(value).height();
			if (offset > footer_start) footer_start = offset;
		}
	});
	if (footer_start < $("#sidebar").height() - 10) $("#sidebar_footer").css("height", $("#sidebar").height() - footer_start - 10);
	else $("#sidebar_footer").css("height", 0);
}

window.print = function() {
  scrollnav();
  // do stuff
  _print();
}

$(document).ready(function() {
	
	$(window).scroll(scrollnav);
	scrollnav();
	scrollnav();
});