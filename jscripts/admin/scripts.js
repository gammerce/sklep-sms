jQuery.fn.scrollTo = function (elem, speed) {
	$(this).animate({
		scrollTop: $(this).scrollTop() - $(this).offset().top + $(elem).offset().top
	}, speed == undefined ? 1000 : speed);
	return this;
};

// Wyszukiwanie usługi
$(document).delegate(".table_structure .search", "submit", function (e) {
	e.preventDefault();

	var search_text = $(this).find(".search_text").val();

	var new_url = "";
	var prmstr = window.location.search.substr(1);
	if (prmstr != null && prmstr != "") {
		var prmarr = prmstr.split("&");
		var search_exists = false;
		for (var i = 0; i < prmarr.length; i++) {
			var tmparr = prmarr[i].split("=");

			if (tmparr[0] == "search") {
				search_exists = true;
				if (search_text == "")
					continue;

				tmparr[1] = encodeURIComponent(search_text);
			}

			if (tmparr[0] == "page")
				continue;

			new_url += (new_url ? "&" : "") + tmparr[0] + "=" + tmparr[1];
		}

		if (!search_exists)
			new_url += (new_url ? "&" : "?") + "search=" + encodeURIComponent(search_text);
	}

	window.location.href = window.location.href.split('?')[0] + (new_url ? "?" + new_url : "");

});

/**
 * Tworzy okienko akcji danej strony
 *
 * @param string page_id
 * @param string box_id
 */
function show_action_box(page_id, box_id, data) {
	data = typeof data !== "undefined" ? data : {};

	data['page_id'] = page_id;
	data['box_id'] = box_id;
	fetch_data("get_action_box", true, data, function(content) {
		if (!(jsonObj = json_parse(content)))
			return;

		// Nie udalo sie prawidlowo pozyskac danych
		if (jsonObj.return_id != "ok") {
			alert(jsonObj.text);
			location.reload();
		}

		action_box.show(jsonObj.template);
	});
}