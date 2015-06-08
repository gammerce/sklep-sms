// Kliknięcie dodania serwera
$(document).delegate("#button_add_server", "click", function () {
	action_box.create();
	getnset_template(action_box.box, "admin_add_server", true, {}, function () {
		action_box.show();
	});
});

// Kliknięcie edycji serwera
$(document).delegate("[id^=edit_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
	action_box.create();
	getnset_template(action_box.box, "admin_edit_server", true, {
		id: row_id.children("td[headers=id]").text()
	}, function () {
		action_box.show();
	});
});

// Usuwanie serwera
$(document).delegate("[id^=delete_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));

	var confirm_info = "Na pewno chcesz usunąć serwer:\n(" + row_id.children("td[headers=id]").text() + ") " + row_id.children("td[headers=name]").text() + " ?";
	if (confirm(confirm_info) == false) {
		return;
	}

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: {
			action: "delete_server",
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
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy usuwaniu serwera.", false);
		}
	});
});

// Dodanie serwera
$(document).delegate("#form_add_server", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=add_server",
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
					var id = $("#form_add_server [name=\"" + name + "\"]");
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
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy dodawaniu serwera.", false);
		}
	});
});

// Edycja serwera
$(document).delegate("#form_edit_server", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=edit_server",
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
					var id = $("#form_edit_server [name=\"" + name + "\"]");
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
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy edytowaniu serwera.", false);
		}
	});
});