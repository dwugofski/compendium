
export function init(){
	$('#navopt_dd_view').click(goto_view);

	$.each($('#books li'), (i, obj) => {
		$(obj).click({id: $(obj).attr("id")}, (e) => {
			goto_page(e.data.id);
		});
	});
}

function goto_page(page_sel){
	var url_obj = getJsonFromUrl();
	url_obj['page_id'] = page_sel;
	location.href = getUrlFromJson(url_obj);
}

function goto_view() {
	location.href = getUrlFromJson({'context' : 'view'});
}

$(document).ready(init);