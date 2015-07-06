// Kliknięcie dodania pytania antyspamowego
$(document).delegate("#button_add_antispam_question", "click", function () {
	show_action_box(get_get_param("pid"), "add_antispam_question");
});

// Kliknięcie edycji pytania antyspamowego
$(document).delegate("[id^=edit_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
	show_action_box(get_get_param("pid"), "edit_antispam_question", {
		id: row_id.children("td[headers=id]").text()
	});
});

// Usuwanie pytania antyspamowego
$(document).delegate("[id^=delete_row_]", "click", function () {
	var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: {
			action: "delete_antispam_question",
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

// Dodanie pytania antyspamowego
$(document).delegate("#form_add_antispam_question", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=add_antispam_question",
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
					var id = $("#form_add_antispam_question [name=\"" + name + "\"]");
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

// Edycja pytania antyspamowego
$(document).delegate("#form_edit_antispam_question", "submit", function (e) {
	e.preventDefault();
	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp_admin.php",
		data: $(this).serialize() + "&action=edit_antispam_question",
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
					var id = $("#form_edit_antispam_question [name=\"" + name + "\"]");
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