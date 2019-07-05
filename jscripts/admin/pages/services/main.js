// Kliknięcie dodania usługi
$(document).delegate("#service_button_add", "click", function() {
    show_action_box(currentPage, "service_add");
});

// Kliknięcie edycji usługi
$(document).delegate(".table_structure .edit_row", "click", function() {
    show_action_box(currentPage, "service_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

// Zmiana modułu usługi
var extra_fields;
$(document).delegate(".action_box [name=module]", "change", function() {
    // Brak wybranego modułu
    if ($(this).val() == "") {
        if (extra_fields) {
            extra_fields.remove();
        }

        return;
    }

    fetch_data(
        "get_service_module_extra_fields",
        true,
        {
            module: $(this).val(),
            service: $(".action_box [name=id2]").val(),
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

// Usuwanie usługi
$(document).delegate(".table_structure .delete_row", "click", function() {
    var row_id = $(this).closest("tr");

    var confirm_info =
        "Na pewno chcesz usunąć usługę:\n(" +
        row_id.children("td[headers=id]").text() +
        ") " +
        row_id.children("td[headers=name]").text() +
        " ?";
    if (confirm(confirm_info) == false) {
        return;
    }

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "delete_service",
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
                refresh_blocks("admincontent", true);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Dodanie Usługi
$(document).delegate("#form_service_add", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=service_add",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function(name, text) {
                    var id = $('#form_service_add [name="' + name + '"]');
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            } else if (jsonObj.return_id == "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent", true);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            // Wyświetlenie zwróconego info
            if (typeof jsonObj.length !== "undefined")
                infobox.show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
            else infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Edycja usługi
$(document).delegate("#form_service_edit", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=service_edit",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function(name, text) {
                    var id = $('#form_service_edit [name="' + name + '"]');
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            } else if (jsonObj.return_id == "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent", true);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
