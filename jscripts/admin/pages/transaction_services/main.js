$(document).delegate(".table_structure .edit_row", "click", function () {
    show_action_box(get_get_param("pid"), "transaction_service_edit", {
        id: $(this).closest('tr').find("td[headers=id]").text()
    });
});

$(document).delegate("#form_transaction_service_edit", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=transaction_service_edit",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == 'ok') {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");
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
