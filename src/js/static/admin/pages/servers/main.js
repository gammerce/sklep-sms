(function() {
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
        notificationContainer.html(notification);
    }

    $(document).delegate("#server_button_add", "click", function() {
        show_action_box(currentPage, "server_add");
    });

    $(document).delegate(".table-structure .edit_row", "click", function() {
        show_action_box(currentPage, "server_edit", {
            id: $(this)
                .closest("tr")
                .find("td[headers=id]")
                .text(),
        });
    });

    $(document).delegate(".table-structure .regenerate-token", "click", function() {
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
                    showNotification("Nowy token: " + jsonObj.data.token);
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });

    $(document).delegate(".table-structure .delete_row", "click", function() {
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

    $(document).delegate("#form_server_add", "submit", function(e) {
        e.preventDefault();

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("/api/admin/servers"),
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
                    showWarnings($("#form_server_add"), jsonObj.warnings);
                } else if (jsonObj.return_id === "ok") {
                    clearAndHideActionBox();
                    refresh_blocks("admincontent");
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });

    // Edit server
    $(document).delegate("#form_server_edit", "submit", function(e) {
        e.preventDefault();

        var serverId = $(this)
            .find("[name=id]")
            .val();

        loader.show();
        $.ajax({
            type: "PUT",
            url: buildUrl("/api/admin/servers/" + serverId),
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
                    showWarnings($("#form_server_edit"), jsonObj.warnings);
                } else if (jsonObj.return_id === "ok") {
                    clearAndHideActionBox();
                    refresh_blocks("admincontent");
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });
})();
