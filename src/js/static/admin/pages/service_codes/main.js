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
        // Let's remove additional fields
        if (extra_fields) {
            extra_fields.remove();
        }
        return;
    }

    var serviceId = $(this).val();

    restRequest("GET", "/api/admin/services/" + serviceId + "/service_codes/add_form", {}, function(
        content
    ) {
        // Let's remove additional fields
        if (extra_fields) {
            extra_fields.remove();
        }

        // Add content to the action box
        extra_fields = $(content);
        extra_fields.insertAfter(".action_box .ftbody");
    });
});

// Delete service code
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var serviceCodeId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/service_codes/" + serviceCodeId),
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

// Dodanie kodu na usługę
$(document).delegate("#form_service_code_add", "submit", function(e) {
    e.preventDefault();
    loader.show();

    var serviceId = $(this).find("[name=service]");

    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services/" + serviceId + "service_codes"),
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
                showWarnings($("#form_service_code_add"), jsonObj.warnings);
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
