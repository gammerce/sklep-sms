import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

function showNotification(message) {
    var tableContainer = $(".table-structure .table-container");
    var notificationContainer = $("#notification_container");

    if (!notificationContainer.length) {
        notificationContainer = $("<div>", {
            id: "notification_container",
        });
        tableContainer.prepend(notificationContainer);
    }

    var notification = $("<div>", {
        class: "notification is-success",
        html: message,
    });
    notificationContainer.html(notification as any);
}

$(document).delegate("#server_button_add", "click", function () {
    showActionBox(window.currentPage, "add");
});

$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

$(document).delegate(".table-structure .regenerate-token", "click", function () {
    var rowId = $(this).closest("tr");
    var serverId = rowId.children("td[headers=id]").text();
    var serverName = rowId.children("td[headers=name]").text();

    var confirmText =
        "Na pewno chcesz wygenerować nowy token dla serwera:\n(" +
        serverId +
        ") " +
        serverName +
        " ?";
    if (!confirm(confirmText)) {
        return;
    }

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/servers/" + serverId + "/token"),
        complete() {
            loader.hide();
        },
        success(content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                showNotification("Nowy token: " + content.data.token);
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate(".table-structure .delete_row", "click", function () {
    var rowId = $(this).closest("tr");
    var serverId = rowId.children("td[headers=id]").text();
    var serverName = rowId.children("td[headers=name]").text();
    var confirmText = "Na pewno chcesz usunąć serwer:\n(" + serverId + ") " + serverName + " ?";

    if (!confirm(confirmText)) {
        return;
    }

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/servers/" + serverId),
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

$(document).delegate("#form_server_add", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/servers"),
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
                showWarnings($("#form_server_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit server
$(document).delegate("#form_server_edit", "submit", function (e) {
    e.preventDefault();

    var serverId = $(this).find("[name=id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/servers/" + serverId),
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
                showWarnings($("#form_server_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
