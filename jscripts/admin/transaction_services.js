$(document).delegate("[id^=edit_row_]", "click", function () {
    var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
    action_box.create();
    getnset_template(action_box.box, "admin_edit_transaction_service", true, {
        id: row_id.children("td[headers=id]").text()
    }, function () {
        action_box.show();
    });
});

$(document).delegate("#form_edit_transaction_service", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: $(this).serialize() + "&action=edit_transaction_service",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == "edited") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy edytowaniu metody płatności.", false);
        }
    });
});