// Kliknięcie dodania pytania antyspamowego
$(document).delegate("#antispam_question_button_add", "click", function() {
    show_action_box(currentPage, "antispam_question_add");
});

// Kliknięcie edycji pytania antyspamowego
$(document).delegate(".table-structure .edit_row", "click", function() {
    show_action_box(currentPage, "antispam_question_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

// Usuwanie pytania antyspamowego
$(document).delegate(".table-structure .delete_row", "click", function() {
    var row_id = $(this).closest("tr");

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "delete_antispam_question",
            id: row_id.children("td[headers=id]").text(),
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id == "ok") {
                // Usuń row
                row_id.fadeOut("slow");
                row_id.css({ background: "#FFF4BA" });

                // Odśwież stronę
                refresh_blocks("admincontent");
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Dodanie pytania antyspamowego
$(document).delegate("#form_antispam_question_add", "submit", function(e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=antispam_question_add",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_antispam_question_add"), jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent");
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Edycja pytania antyspamowego
$(document).delegate("#form_antispam_question_edit", "submit", function(e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=antispam_question_edit",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_antispam_question_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent");
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
