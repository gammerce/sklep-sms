import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";

$(document).delegate("#payment_platform_button_add", "click", function() {
    showActionBox(currentPage, "create");
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

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($(that), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
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
    showActionBox(currentPage, "edit", {
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
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($(that), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
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

    if (confirm(confirmText) === false) {
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
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
