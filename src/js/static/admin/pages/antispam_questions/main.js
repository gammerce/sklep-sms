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
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "ok") {
                // Delete row
                row_id.fadeOut("slow");
                row_id.css({ background: "#FFF4BA" });

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
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

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_antispam_question_add"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
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

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_antispam_question_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
