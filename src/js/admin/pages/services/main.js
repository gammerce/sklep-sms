import { clearAndHideActionBox, refreshBlocks, show_action_box } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";
import { json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

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

// Change service module
var serviceExtraFlags;
$(document).delegate(".action_box [name=module]", "change", function() {
    var moduleId = $(this).val();

    if (!moduleId && serviceExtraFlags) {
        serviceExtraFlags.remove();
        return;
    }

    var serviceId = $(".action_box [name=id]");

    restRequest(
        "GET",
        "/api/admin/services/" + serviceId + "/modules/" + moduleId + "/extra_fields",
        {},
        function(content) {
            if (serviceExtraFlags) {
                serviceExtraFlags.remove();
            }

            serviceExtraFlags = $(content);
            serviceExtraFlags.insertAfter(".action_box .ftbody");
        }
    );
});

// Delete service
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var serviceId = rowId.children("td[headers=id]").text();
    var serviceName = rowId.children("td[headers=name]").text();

    var confirmText = "Na pewno chcesz usunąć usługę:\n(" + serviceId + ") " + serviceName + " ?";
    if (confirm(confirmText) == false) {
        return;
    }

    loader.show();
    $.ajax({
        type: "DELETE",
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

                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Add service
$(document).delegate("#form_service_add", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services"),
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
                showWarnings($("#form_service_add"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refreshBlocks("admincontent");
            }

            if (typeof jsonObj.length !== "undefined")
                infobox.show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
            else infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit service
$(document).delegate("#form_service_edit", "submit", function(e) {
    e.preventDefault();

    var serviceId = $(this)
        .find("[name=id]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/services/" + serviceId),
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
                showWarnings($("#form_service_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
