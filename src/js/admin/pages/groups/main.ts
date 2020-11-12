import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl } from "../../../general/global";

$(document).delegate("#group_button_add", "click", function () {
    showActionBox(window.currentPage, "add");
});

$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

// Delete group
$(document).delegate(".table-structure .delete_row", "click", function () {
    var rowId = $(this).closest("tr");
    var groupId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/groups/" + groupId),
        complete: function () {
            loader.hide();
        },
        success: function (content) {
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

// Add group
$(document).delegate("#form_group_add", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/groups"),
        data: $(this).serialize(),
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit group
$(document).delegate("#form_group_edit", "submit", function (e) {
    e.preventDefault();

    var groupId = $(this).find("[name=id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/groups/" + groupId),
        data: $(this).serialize(),
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
