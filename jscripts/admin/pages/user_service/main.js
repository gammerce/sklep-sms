// Kliknięcie dodania usługi użytkownika
$(document).delegate("#user_service_button_add", "click", function() {
    show_action_box(currentPage, "user_service_add");
});

// Kliknięcie edycji usługi użytkownika
$(document).delegate("[id^=edit_row_]", "click", function() {
    var row_id = $(
        "#" +
            $(this)
                .attr("id")
                .replace("edit_row_", "row_")
    );
    show_action_box(currentPage, "user_service_edit", {
        id: row_id.children("td[headers=id]").text(),
    });
});
$(document).delegate(".table_structure .edit_row", "click", function() {
    show_action_box(currentPage, "user_service_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

// Wybranie modułu
$(document).delegate("#user_service_display_module", "change", function() {
    changeUrl({
        subpage: $(this).val(),
    });
});

// Ustawienie na zawsze
$(document).delegate("#form_user_service_add [name=forever]", "change", function() {
    if ($(this).prop("checked")) $("#form_user_service_add [name=amount]").prop("disabled", true);
    else $("#form_user_service_add [name=amount]").prop("disabled", false);
});

// Wybranie usługi podczas dodawania usługi użytkownikowi
var extra_fields;
$(document).delegate("#form_user_service_add [name=service]", "change", function() {
    // Brak wybranego modułu
    if ($(this).val() == "") {
        // Usuwamy dodatkowe pola
        if (extra_fields) extra_fields.remove();

        return;
    }

    fetch_data(
        "user_service_add_form_get",
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

// Usuwanie usługi użytkownika
$(document).delegate("[id^=delete_row_]", "click", function() {
    var row_id = $(
        "#" +
            $(this)
                .attr("id")
                .replace("delete_row_", "row_")
    );

    var confirm_info =
        "Na pewno chcesz usunąć usluge o ID: " + row_id.children("td[headers=id]").text() + " ?";
    if (confirm(confirm_info) == false) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "user_service_delete",
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

$(document).delegate(".table_structure .delete_row", "click", function() {
    var row_id = $(this).closest("tr");

    var confirm_info =
        "Na pewno chcesz usunąć usluge o ID: " + row_id.children("td[headers=id]").text() + " ?";
    if (confirm(confirm_info) == false) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "user_service_delete",
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

// Dodanie usługi użytkownikowi
$(document).delegate("#form_user_service_add", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=user_service_add",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            $(".form_warning").remove(); // Usuniecie komunikatow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function(name, text) {
                    var id = $('#form_user_service_add [name="' + name + '"]');
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

// Edycja usługi użytkownika
$(document).delegate("#form_user_service_edit", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=user_service_edit",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function(name, text) {
                    var id = $('#form_user_service_edit [name="' + name + '"]');
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
