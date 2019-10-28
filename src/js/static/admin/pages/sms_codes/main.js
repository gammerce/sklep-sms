// Kliknięcie dodania kodu SMS
$(document).delegate("#sms_code_button_add", "click", function() {
    show_action_box(currentPage, "sms_code_add");
});

// Kliknięcie przycisku generuj kod
$(document).delegate("#form_sms_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

// Usuwanie kodu SMS
$(document).delegate(".table-structure .delete_row", "click", function() {
    var row_id = $(this).closest("tr");

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "delete_sms_code",
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

// Dodanie kodu SMS
$(document).delegate("#form_sms_code_add", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=sms_code_add",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_sms_code_add"), jsonObj.warnings);
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
