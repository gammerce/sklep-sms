(function() {
    function clearAndHideActionBox() {
        action_box.hide();
        $("#action_box_wraper_td").html("");
    }

    // This is used later when action is done
    var row_id = 0;
    $(document).delegate(".table-structure .charge_wallet", "click", function() {
        row_id = $(this).closest("tr");
        show_action_box(currentPage, "charge_wallet", {
            uid: row_id.children("td[headers=id]").text(),
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
        var row_id = $(this).closest("tr");

        if (
            !confirm(
                "Czy na pewno chcesz usunąć konto o ID " +
                    row_id.children("td[headers=id]").text() +
                    "?"
            )
        ) {
            return;
        }

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("jsonhttp_admin.php"),
            data: {
                action: "delete_user",
                uid: row_id.children("td[headers=id]").text(),
            },
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
                    row_id.fadeOut("slow");
                    row_id.css({ background: "#FFF4BA" });

                    refresh_blocks("admincontent");
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });

    $(document).delegate("#form_charge_wallet", "submit", function(e) {
        e.preventDefault();
        loader.show();
        var that = this;
        $.ajax({
            type: "POST",
            url: buildUrl("jsonhttp_admin.php"),
            data: $(this).serialize() + "&action=charge_wallet",
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
                    getnset_template(
                        row_id.children("td[headers=wallet]"),
                        "admin_user_wallet",
                        {
                            uid: $(that)
                                .find("input[name=uid]")
                                .val(),
                        },
                        function() {
                            // Podświetl row
                            row_id.children("td[headers=wallet]").effect("highlight", 1000);
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
        loader.show();
        var that = this;

        var userId = $(this)
            .find("input[name=uid]")
            .val();

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
        loader.show();

        var that = this;
        var userId = $(that)
            .find("[name=uid]")
            .val();

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
})();
