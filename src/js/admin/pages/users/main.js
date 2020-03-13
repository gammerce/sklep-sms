import { clearAndHideActionBox, getAndSetTemplate, show_action_box } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";
import { json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { refresh_blocks } from "../../../shop/utils/utils";

// This is used later when action is done
var rowId = 0;
$(document).delegate(".table-structure .charge_wallet", "click", function() {
    rowId = $(this).closest("tr");
    show_action_box(currentPage, "charge_wallet", {
        uid: rowId.children("td[headers=id]").text(),
    });
});

$(document).delegate(".table-structure .change_password", "click", function() {
    show_action_box(currentPage, "change_password", {
        uid: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

$(document).delegate(".table-structure .edit_row", "click", function() {
    show_action_box(currentPage, "user_edit", {
        uid: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var userId = rowId.children("td[headers=id]").text();

    if (!confirm("Czy na pewno chcesz usunąć konto o ID " + userId + "?")) {
        return;
    }

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/users/" + userId),
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

$(document).delegate("#form_charge_wallet", "submit", function(e) {
    e.preventDefault();

    var that = this;
    var userId = $(this)
        .find("[name=uid]")
        .val();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/users/" + userId + "/wallet/charge"),
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
            } else if (jsonObj.return_id === "charged") {
                // Change wallet state
                getAndSetTemplate(
                    rowId.children("td[headers=wallet]"),
                    "admin_user_wallet",
                    {
                        uid: $(that)
                            .find("input[name=uid]")
                            .val(),
                    },
                    function() {
                        // Podświetl row
                        rowId.children("td[headers=wallet]").effect("highlight", 1000);
                    }
                );

                clearAndHideActionBox();
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_change_password", "submit", function(e) {
    e.preventDefault();
    var that = this;

    var userId = $(this)
        .find("input[name=uid]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/users/" + userId + "/password"),
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
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_user_edit", "submit", function(e) {
    e.preventDefault();

    var that = this;
    var userId = $(that)
        .find("[name=uid]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/users/" + userId),
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
                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
