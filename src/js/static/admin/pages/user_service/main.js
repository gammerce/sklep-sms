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
$(document).delegate(".table-structure .edit_row", "click", function() {
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

    var serviceId = $(this).val();

    restRequest("GET", "/api/admin/services/" + serviceId + "/user_services/add_form", {}, function(
        content
    ) {
        // Usuwamy dodatkowe pola
        if (extra_fields) extra_fields.remove();

        // Dodajemy content do action boxa
        extra_fields = $(content);
        extra_fields.insertAfter(".action_box .ftbody");
    });
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
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "ok") {
                // Delete row
                row_id.fadeOut("slow");
                row_id.css({ background: "#FFF4BA" });

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var userServiceId = rowId.children("td[headers=id]").text();

    var confirmInfo = "Na pewno chcesz usunąć usluge o ID: " + userServiceId + " ?";
    if (confirm(confirmInfo) == false) {
        return;
    }

    loader.show();

    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/user_services/" + userServiceId),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "ok") {
                // Delete row
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Dodanie usługi użytkownikowi
$(document).delegate("#form_user_service_add", "submit", function(e) {
    e.preventDefault();
    loader.show();

    var serviceId = $(this).find("[name=service]");

    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services/" + serviceId + "/user_services"),
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
                showWarnings($("#form_user_service_add"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Edycja usługi użytkownika
$(document).delegate("#form_user_service_edit", "submit", function(e) {
    e.preventDefault();
    loader.show();

    var userServiceId = $(this).find("[name=id]");

    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/user_services/" + userServiceId),
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
                showWarnings($("#form_user_service_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
