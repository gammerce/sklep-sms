$(document).delegate("#form_settings_edit", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=settings_edit",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_settings_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
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
