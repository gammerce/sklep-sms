// Usuwanie logu
$(document).delegate("[id^=delete_row_]", "click", function () {
    var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: {
            action: "delete_log",
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
                refresh_brick("logs", true);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy usuwaniu logu.", false);
        }
    });
});