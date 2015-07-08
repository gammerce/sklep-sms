$(document).delegate("#form_take_over_service [name=service]", "change", function () {
	if ($(this).val() == "") {
		$("#form_take_over_service .extra_data").html("");
		$("#form_take_over_service .take_over").hide();
		return;
	}

	var data = {
		service: $(this).val()
	};
	fetch_data("form_take_over_service", false, data, function (html) {
		$("#form_take_over_service .extra_data").html(html);
		$("#form_take_over_service .take_over").show();
	});
});

$(document).delegate("#form_take_over_service", "submit", function (e) {
	e.preventDefault();

	if (loader.blocked)
		return;

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp.php",
		data: $(this).serialize() + "&action=take_over_service",
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
					var id = $("#form_take_over_service [name=\"" + name + "\"]:first");
					id.parent().append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "ok") {
				// Przejdź do strony user_own_services
				setTimeout(function () {
					window.location.href = "../../../index.php";
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