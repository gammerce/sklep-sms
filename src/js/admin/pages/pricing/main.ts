import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#price_button_add", "click", function () {
    showActionBox(window.currentPage, "add");
});

$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

// Delete price
$(document).delegate(".table-structure .delete_row", "click", function () {
    var rowId = $(this).closest("tr");
    var priceId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/prices/" + priceId),
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

// Add price
$(document).delegate("#form_price_add", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/prices"),
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
                showWarnings($("#form_price_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit price
$(document).delegate("#form_price_edit", "submit", function (e) {
    e.preventDefault();

    var priceId = $(this).find("[name=id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/prices/" + priceId),
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
                showWarnings($("#form_price_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
