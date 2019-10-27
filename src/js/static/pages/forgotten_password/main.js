$(document).delegate("#form_forgotten_password", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/password/forgotten"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_forgotten_password"), jsonObj.warnings);
            } else if (jsonObj.return_id == "sent") {
                // Wyświetl informacje o wysłaniu maila
                getnset_template($("#content"), "forgotten_password_sent", {
                    username: jsonObj.username,
                });
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
