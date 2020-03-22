import { clearAndHideActionBox, refreshBlocks, show_action_box } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";
import { get_random_string, json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

$(document).delegate("#service_code_button_add", "click", function() {
    show_action_box(currentPage, "code_add");
});

// Generate code
$(document).delegate("#form_service_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

// Selecting a service while adding service code
var serviceCodeAddForm;
$(document).delegate("#form_service_code_add [name=service_id]", "change", function() {
    var serviceId = $(this).val();

    if (!serviceId && serviceCodeAddForm) {
        serviceCodeAddForm.remove();
        return;
    }

    restRequest("GET", "/api/admin/services/" + serviceId + "/service_codes/add_form", {}, function(
        content
    ) {
        if (serviceCodeAddForm) {
            serviceCodeAddForm.remove();
        }

        serviceCodeAddForm = $(content);
        serviceCodeAddForm.insertAfter(".action_box .ftbody");
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

                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Add service code
$(document).delegate("#form_service_code_add", "submit", function(e) {
    e.preventDefault();

    var serviceId = $(this)
        .find("[name=service_id]")
        .val();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services/" + serviceId + "/service_codes"),
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
                clearAndHideActionBox();
                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Change server
$(document).delegate("#form_service_code_add [name=server_id]", "change", function() {
    var form = $(this).closest("form");

    if (!$(this).val().length) {
        form.find("[name=price_id]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        "/api/admin/services/" + serviceId + "/actions/prices_for_server",
        {
            server_id: $(this).val(),
        },
        function(html) {
            form.find("[name=price_id]").html(html);
        }
    );
});
