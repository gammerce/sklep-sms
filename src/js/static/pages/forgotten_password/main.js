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

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_forgotten_password"), jsonObj.warnings);
            } else if (jsonObj.return_id === "sent") {
                // Wyświetl informacje o wysłaniu maila
                getnset_template($("#content"), "forgotten_password_sent", {
                    username: jsonObj.username,
                });
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
