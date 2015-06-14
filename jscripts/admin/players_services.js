// Kliknięcie dodania usługi gracza
$(document).delegate("#button_add_user_service", "click", function () {
	action_box.create();
	getnset_template(action_box.box, "admin_add_user_service", true, {}, function () {
		action_box.show();
	});
});

// Kliknięcie edycji usługi gracza
$(document).delegate("[id^=edit_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
	action_box.create();
	getnset_template(action_box.box, "admin_edit_user_service", true, {
		id: row_id.children("td[headers=id]").text()
	}, function () {
		action_box.show();
		$("#form_edit_user_service [name=type]").trigger("change");
	});
});

// Wybranie usługi podczas dodawania usługi graczowi
var extra_fields, extra_scripts;
$(document).delegate("#form_add_user_service [name=service]", "change", function () {
	// Brak wybranego modułu
	if ($(this).val() == "") {
		// Usuwamy dodatkowe pola
		if (extra_fields) {
			extra_fields.remove();
		}
		if (extra_scripts) {
			extra_scripts.remove();
		}
		return;
	}

	fetch_data("get_add_user_service_form", true, {
		service: $(this).val()
	}, function (content) {
		if (!(jsonObj = json_parse(content)))
			return;

		// Usuwamy dodatkowe pola
		if (extra_fields) {
			extra_fields.remove();
		}
		if (extra_scripts) {
			extra_scripts.remove();
		}

		// Dodajemy content do action boxa
		extra_fields = $("<tbody>", {
			html: jsonObj.text
		});
		extra_fields.insertAfter(".action_box .ftbody");

		// Dodajemy skrypty
		extra_scripts = $(jsonObj.scripts);
		extra_scripts.insertAfter("head");
	});
});

// Usuwanie usługi gracza
$(document).delegate("[id^=delete_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));

	var confirm_info = "Na pewno chcesz usunąć usluge o ID: " + row_id.children("td[headers=id]").text() + " ?";
	if (confirm(confirm_info) == false) {
		return;
	}

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: {
			action: "delete_player_service",
			id: row_id.children("td[headers=id]").text()
		},
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

			if (jsonObj.return_id == "deleted") {
				// Usuń row
				row_id.fadeOut("slow");
				row_id.css({"background": "#FFF4BA"});

				// Odśwież stronę
				refresh_blocks("admincontent", true);
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

// Dodanie usługi gracza
$(document).delegate("#form_add_user_service", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=add_user_service",
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
					var id = $("#form_add_user_service [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "added") {
				// Ukryj i wyczyść action box
				action_box.hide();
				$("#action_box_wraper_td").html("");

				// Odśwież stronę
				refresh_blocks("admincontent", true);
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

// Edycja usługi gracza
$(document).delegate("#form_edit_user_service", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=edit_user_service",
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
					var id = $("#form_edit_user_service [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "edited") {
				// Ukryj i wyczyść action box
				action_box.hide();
				$("#action_box_wraper_td").html("");

				// Odśwież stronę
				refresh_blocks("admincontent", true);
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
