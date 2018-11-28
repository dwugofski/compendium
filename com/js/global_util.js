
function getJsonFromUrl(url=undefined) {
	if (url === undefined) url = location.href;
	var query;
	var pos = url.indexOf("?");
	if(pos==-1) return [];
	query = url.substr(pos+1);
	var result = {};
	query.split("&").forEach(function(part) {
		if(!part) return;

		part = part.split("+").join(" "); // replace every + with space, regexp-free version
		var eq = part.indexOf("=");
		var key = eq>-1 ? part.substr(0,eq) : part;
		var val = eq>-1 ? decodeURIComponent(part.substr(eq+1)) : "";
		var from = key.indexOf("[");

		if(from==-1) result[decodeURIComponent(key)] = val;
		else {
			var to = key.indexOf("]",from);
			var index = decodeURIComponent(key.substring(from+1,to));
			key = decodeURIComponent(key.substring(0,from));
			if(!result[key]) result[key] = [];
			if(!index) result[key].push(val);
			else result[key][index] = val;
		}
	});
	return result;
}

function getUrlFromJson(obj, original_url=undefined) {
	if (original_url === undefined) original_url = location.href;
	var composed_url = "";

	const pos = original_url.indexOf("?");
	if (pos == -1) composed_url = original_url + "?";
	else composed_url = original_url.substring(0, pos+1);

	var prefix = "";
	Object.keys(obj).forEach((key) => {
		composed_url += prefix + encodeURIComponent(key) + "=" + encodeURIComponent(obj[key]);
		prefix = "&";
	});

	return composed_url;
}