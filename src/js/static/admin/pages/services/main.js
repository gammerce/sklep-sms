// Kliknięcie dodania usługi
$(document).delegate("#service_button_add", "click", function() {
    show_action_box(currentPage, "service_add");
});

// Kliknięcie edycji usługi
$(document).delegate(".table-structure .edit_row", "click", function() {
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

    var moduleId = $(this).val();
    var serviceId = $(".action_box [name=id2]");

    restRequest(
        "GET",
        "/api/admin/services/" + serviceId + "/modules/" + moduleId + "/extra_fields",
        {},
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
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var serviceId = rowId.children("td[headers=id]").text();
    var serviceName = rowId.children("td[headers=name]").text();

    var confirmInfo = "Na pewno chcesz usunąć usługę:\n(" + serviceId + ") " + serviceName + " ?";
    if (confirm(confirmInfo) == false) {
        return;
    }

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services/" + serviceId),
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
            removeFormWarnings();

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_service_add"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                refresh_blocks("admincontent");
            }

            if (typeof jsonObj.length !== "undefined")
                infobox.show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
            else infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
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
            removeFormWarnings();

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_service_edit"), jsonObj.warnings);
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
