$(document).delegate("#form_change_password", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/password"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_change_password"), jsonObj.warnings);
            } else if (jsonObj.return_id == "password_changed") {
                // Wyświetl informacje o zmianie hasła
                getnset_template($("#content"), "reset_password_changed");
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
