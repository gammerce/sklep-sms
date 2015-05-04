$(document).delegate("#form_edit_settings", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=edit_settings",
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			$(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

			if (!(jsonObj = json_parse(content)))
				return;

			// Wyświetlenie błędów w formularzu
			if (jsonObj.return_id == "warnings") {
				$.each(jsonObj.warnings, function (name, text) {
					var id = $("#form_edit_settings [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "edited") {
				// Odśwież stronę
				refresh_brick("settings", true);
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy wprowadzaniu zmian w ustawieniach.", false);
		}
	});
});