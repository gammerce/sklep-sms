import {
    clearAndHideActionBox,
    getAndSetTemplate,
    refreshAdminContent,
    showActionBox,
} from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

// This is used later when action is done
let rowId: any = 0;
$(document).delegate(".table-structure .charge_wallet", "click", function () {
    rowId = $(this).closest("tr");
    showActionBox(window.currentPage, "charge_wallet", {
        user_id: rowId.children("td[headers=id]").text(),
    });
});

$(document).delegate(".table-structure .change_password", "click", function () {
    showActionBox(window.currentPage, "change_password", {
        user_id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        user_id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

$(document).delegate(".table-structure .delete_row", "click", function () {
    var rowId = $(this).closest("tr");
    var userId = rowId.children("td[headers=id]").text();

    if (!confirm("Czy na pewno chcesz usunąć konto o ID " + userId + "?")) {
        return;
    }

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/users/" + userId),
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

$(document).delegate("#form_charge_wallet", "submit", function (e) {
    e.preventDefault();

    var that = this;
    var userId = $(this).find("[name=user_id]").val();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl(`/api/admin/users/${userId}/wallet/charge`),
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
                showWarnings($(that), content.warnings);
            } else if (content.return_id === "charged") {
                // Change wallet state
                getAndSetTemplate(
                    rowId.children("td[headers=wallet]"),
                    "admin_user_wallet",
                    {
                        user_id: $(that).find("input[name=user_id]").val(),
                    },
                    function () {
                        // Podświetl row
                        rowId.children("td[headers=wallet]").effect("highlight", 1000);
                    }
                );

                clearAndHideActionBox();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_change_password", "submit", function (e) {
    e.preventDefault();
    var that = this;

    var userId = $(this).find("input[name=user_id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/users/" + userId + "/password"),
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
                showWarnings($(that), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_user_edit", "submit", function (e) {
    e.preventDefault();

    var that = this;
    var userId = $(that).find("[name=user_id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/users/" + userId),
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
