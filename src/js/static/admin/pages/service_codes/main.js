// Kliknięcie dodania kodu na usługę
$(document).delegate("#service_code_button_add", "click", function() {
    show_action_box(currentPage, "code_add");
});

// Kliknięcie przycisku generuj kod
$(document).delegate("#form_service_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

// Wybranie usługi podczas dodawania kodu na usługę
var extra_fields;
$(document).delegate("#form_service_code_add [name=service]", "change", function() {
    // Brak wybranej usługi
    if (!$(this).val().length) {
        // Usuwamy dodatkowe pola
        if (extra_fields) extra_fields.remove();
        return;
    }

    fetch_data(
        "service_code_add_form_get",
        true,
        {
            service: $(this).val(),
        },
        function(content) {
            // Usuwamy dodatkowe pola
            if (extra_fields) extra_fields.remove();

            // Dodajemy content do action boxa
            extra_fields = $(content);
            extra_fields.insertAfter(".action_box .ftbody");
        }
    );
});

// Usuwanie kodu na usługę
$(document).delegate(".table-structure .delete_row", "click", function() {
    var row_id = $(this).closest("tr");

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "delete_service_code",
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

// Dodanie kodu na usługę
$(document).delegate("#form_service_code_add", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=service_code_add",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_service_code_add"), jsonObj.warnings);
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
