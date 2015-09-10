// Klikniecie doladowania portfela
var row_id = 0;
$(document).delegate(".table_structure .charge_wallet", "click", function () {
	row_id = $(this).closest('tr');
	show_action_box(get_get_param("pid"), "charge_wallet", {
		uid: row_id.children("td[headers=id]").text()
	});
});

// Kliknięcie edycji użytkownika
$(document).delegate(".table_structure .edit_row", "click", function () {
	show_action_box(get_get_param("pid"), "user_edit", {
		uid: $(this).closest('tr').find("td[headers=id]").text()
	});
});

// Usuwanie użytkownika
$(document).delegate(".table_structure .delete_row", "click", function () {
	var row_id = $(this).closest('tr');

	// Czy na pewno?
	if (confirm("Czy na pewno chcesz usunąć konto o ID " + row_id.children("td[headers=id]").text() + "?") == false) {
		return;
	}

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: {
			action: "delete_user",
			uid: row_id.children("td[headers=id]").text()
		},
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

			if (jsonObj.return_id == 'ok') {
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

// Doladowanie portfela
$(document).delegate("#form_charge_wallet", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=charge_wallet",
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
					var id = $("#form_charge_wallet [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "charged") {
				// Zmień stan portfela
				getnset_template(row_id.children("td[headers=wallet]"), "admin_user_wallet", true, {
						uid: $("#form_charge_wallet input[name=uid]").val()
					}, function () {
						// Podświetl row
						row_id.children("td[headers=wallet]").effect("highlight", 1000);
					}
				);

				// Ukryj i wyczyść action box
				action_box.hide();
				$("#action_box_wraper_td").html('');
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

// Edycja uzytkownika
$(document).delegate("#form_user_edit", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=user_edit",
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
					var id = $("#form_user_edit [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == 'ok') {
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