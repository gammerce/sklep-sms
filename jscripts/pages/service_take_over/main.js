$(document).delegate("#form_service_take_over [name=service]", "change", function () {
	if ($(this).val() == "") {
		$("#form_service_take_over").find(".extra_data").html('');
		$("#form_service_take_over").find(".take_over").hide();
		return;
	}

	var data = {
		service: $(this).val()
	};
	fetch_data("service_take_over_form_get", false, data, function (html) {
		$("#form_service_take_over .extra_data").html(html);
		$("#form_service_take_over .take_over").show();
	});
});

$(document).delegate("#form_service_take_over", "submit", function (e) {
	e.preventDefault();

	if (loader.blocked)
		return;

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp.php",
		data: $(this).serialize() + "&action=service_take_over",
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			$(".form_warning").remove(); // Usuniecie komunikatow o blednym wypelnieniu formualarza

			if (!(jsonObj = json_parse(content)))
				return;

			// Wyświetlenie błędów w formularzu
			if (jsonObj.return_id == "warnings") {
				$.each(jsonObj.warnings, function (name, text) {
					var id = $("#form_service_take_over [name=\"" + name + "\"]:first");
					id.parent().append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "ok") {
				// Przejdź do strony user_own_services
				setTimeout(function () {
					window.location.href = "index.php?pid=user_own_services";
				}, 2000);
			}
			else if (!jsonObj.return_id) {
				infobox.show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			infobox.show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			infobox.show_info(lang['ajax_error'], false);
		}
	});
});