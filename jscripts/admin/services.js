// Kliknięcie dodania usługi
$(document).delegate("#button_add_service", "click", function () {
	action_box.create();
	getnset_template(action_box.box, "admin_add_service", true, {}, function () {
		action_box.show();
	});
});

// Kliknięcie edycji usługi
$(document).delegate("[id^=edit_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
	action_box.create();
	getnset_template(action_box.box, "admin_edit_service", true, {
		id: row_id.children("td[headers=id]").text()
	}, function () {
		action_box.show();
	});
});

// Zmiana modułu usługi
$(document).delegate(".action_box [name=module]", "change", function () {
	// Brak wybranego modułu
	if ($(this).val() == "") {
		// Usuwamy dodatkowe pola
		$(".action_box .extra_fields").remove();
		return;
	}

	fetch_data("get_service_module_extra_fields", true, {
		module: $(this).val(),
		service: $(".action_box [name=id2]").val()
	}, function (html) {
		// Usuwamy dodatkowe pola
		$(".action_box .extra_fields").remove();

		$("<tbody>", {
			class: 'extra_fields',
			html: html
		}).insertAfter(".action_box .ftbody");
	});
});

// Usuwanie usługi
$(document).delegate("[id^=delete_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));

	var confirm_info = "Na pewno chcesz usunąć usługę:\n(" + row_id.children("td[headers=id]").text() + ") " + row_id.children("td[headers=name]").text() + " ?";
	if (confirm(confirm_info) == false) {
		return;
	}

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: {
			action: "delete_service",
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
				refresh_brick("services", true);
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy usuwaniu usługi.", false);
		}
	});
});

// Dodanie Usługi
$(document).delegate("#form_add_service", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=add_service",
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
					var id = $("#form_add_service [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "added") {
				// Ukryj i wyczyść action box
				action_box.hide();
				$("#action_box_wraper_td").html("");

				// Odśwież stronę
				refresh_brick("services", true);
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			if (typeof(jsonObj.length) !== 'undefined') show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
			else show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy dodawaniu usługi.", false);
		}
	});
});

// Edycja usługi
$(document).delegate("#form_edit_service", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=edit_service",
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
					var id = $("#form_edit_service [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "edited") {
				// Ukryj i wyczyść action box
				action_box.hide();
				$("#action_box_wraper_td").html("");

				// Odśwież stronę
				refresh_brick("services", true);
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy edytowaniu usługi.", false);
		}
	});
});