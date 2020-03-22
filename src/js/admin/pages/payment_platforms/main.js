import { clearAndHideActionBox, refreshBlocks, show_action_box } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";

$(document).delegate("#payment_platform_button_add", "click", function() {
    show_action_box(currentPage, "create");
});

$(document).delegate("#form_payment_platform_add", "submit", function(e) {
    e.preventDefault();

    var that = this;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/payment_platforms"),
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
                showWarnings($(that), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

var formPaymentPlatformAddForm;
$(document).delegate("#form_payment_platform_add [name=module]", "change", function() {
    var paymentModuleId = $(this).val();

    if (!paymentModuleId && formPaymentPlatformAddForm) {
        formPaymentPlatformAddForm.remove();
        return;
    }

    restRequest("GET", "/api/admin/payment_modules/" + paymentModuleId + "/add_form", {}, function(
        content
    ) {
        if (formPaymentPlatformAddForm) {
            formPaymentPlatformAddForm.remove();
        }

        formPaymentPlatformAddForm = $(content);
        formPaymentPlatformAddForm.insertAfter(".action_box .ftbody");
    });
});

// EDIT
$(document).delegate(".table-structure .edit_row", "click", function() {
    show_action_box(currentPage, "edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

$(document).delegate("#form_payment_platform_edit", "submit", function(e) {
    e.preventDefault();

    var paymentPlatformId = $(this)
        .find("[name=id]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/payment_platforms/" + paymentPlatformId),
        data: $(this).serialize(),
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

            if (jsonObj.return_id === "warnings") {
                showWarnings($(that), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// DELETE
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var paymentPlatformId = rowId.children("td[headers=id]").text();
    var paymentPlatformName = rowId.children("td[headers=name]").text();

    var confirmText =
        "Na pewno chcesz usunąć platformę płatności:\n(" +
        paymentPlatformId +
        ") " +
        paymentPlatformName +
        " ?";
    if (confirm(confirmText) == false) {
        return;
    }

    loader.show();

    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/payment_platforms/" + paymentPlatformId),
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
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });
                refreshBlocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
