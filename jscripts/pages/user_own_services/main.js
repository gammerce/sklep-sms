// Kliknięcie edycji usługi
$(document).delegate("#user_own_services .edit_row", "click", function() {
    var row_id = $(this).parents("form:first");

    fetch_data(
        "form_user_service_edit",
        false,
        {
            id: row_id.data("row"),
        },
        function(html) {
            // Podmieniamy zawartość
            row_id.html(html);
            row_id.parents(".brick:first").addClass("active");

            // Dodajemy event, aby powróciło do poprzedniego stanu po kliknięciu "Anuluj"
            row_id.find(".cancel").click({ row_id: row_id }, function(e) {
                var row_id = e.data.row_id;
                fetch_data(
                    "get_user_service_brick",
                    false,
                    {
                        id: row_id.data("row"),
                    },
                    function(html) {
                        // Podmieniamy zawartość
                        row_id.html(html);
                        row_id.parents(".brick:first").removeClass("active");
                    }
                );
            });
        }
    );
});

// Wyedytowanie usługi
$(document).delegate("#user_own_services .row", "submit", function(e) {
    e.preventDefault();

    loader.show();
    var temp_this = $(this);
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp.php"),
        data: $(this).serialize() + "&action=user_service_edit&id=" + temp_this.data("row"),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            temp_this.find(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }
            else if (jsonObj.return_id === "warnings") {
                showWarnings(temp_this, jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
                refresh_blocks("content");
            } else if (jsonObj.return_id == "payment") {
                // Przechodzimy do płatności
                go_to_payment(jsonObj.data, jsonObj.sign);
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
