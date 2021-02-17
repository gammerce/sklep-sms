import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

// Kliknięcie dodania usługi
$(document).delegate("#service_button_add", "click", function () {
    showActionBox(window.currentPage, "add");
});

// Kliknięcie edycji usługi
$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

// Change service module
var serviceExtraFlags;
$(document).delegate(".action_box [name=module]", "change", function () {
    var moduleId = $(this).val();

    if (!moduleId && serviceExtraFlags) {
        serviceExtraFlags.remove();
        return;
    }

    var serviceId = $(".action_box [name=id]");

    restRequest(
        "GET",
        `/api/admin/services/${serviceId}/modules/${moduleId}/extra_fields`,
        {},
        function (content) {
            if (serviceExtraFlags) {
                serviceExtraFlags.remove();
            }

            serviceExtraFlags = $(content);
            serviceExtraFlags.insertAfter(".action_box .ftbody");
        }
    );
});

// Delete service
$(document).delegate(".table-structure .delete_row", "click", function () {
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
        complete() {
            loader.hide();
        },
        success(content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                // Delete row
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });

                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Add service
$(document).delegate("#form_service_add", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services"),
        data: $(this).serialize(),
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_service_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            if (typeof content.length !== "undefined") {
                infobox.showInfo(content.text, content.positive, content.length);
            } else {
                infobox.showInfo(content.text, content.positive);
            }
        },
        error: handleErrorResponse,
    });
});

// Edit service
$(document).delegate("#form_service_edit", "submit", function (e) {
    e.preventDefault();

    var serviceId = $(this).find("[name=id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/services/" + serviceId),
        data: $(this).serialize(),
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_service_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
