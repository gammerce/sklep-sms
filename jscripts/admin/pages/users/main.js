(function() {
    function removeFormWarnings() {
        $(".form_warning").remove();
    }

    function showWarnings(form, warnings) {
        $.each(warnings, function(name, text) {
            var id = form.find('[name="' + name + '"]');
            id.parent("td").append(text);
            id.effect("highlight", 1000);
        });
    }

    function clearAndHideActionBox() {
        action_box.hide();
        $("#action_box_wraper_td").html("");
    }

    function handleErrorResponse() {
        infobox.show_info(lang["ajax_error"], false);
    }

    function sthWentWrong() {
        infobox.show_info(lang["sth_went_wrong"], false);
    }

    // This is used later when action is done
    var row_id = 0;
    $(document).delegate(".table_structure .charge_wallet", "click", function() {
        row_id = $(this).closest("tr");
        show_action_box(currentPage, "charge_wallet", {
            uid: row_id.children("td[headers=id]").text(),
        });
    });

    $(document).delegate(".table_structure .change_password", "click", function() {
        show_action_box(currentPage, "change_password", {
            uid: $(this)
                .closest("tr")
                .find("td[headers=id]")
                .text(),
        });
    });

    $(document).delegate(".table_structure .edit_row", "click", function() {
        show_action_box(currentPage, "user_edit", {
            uid: $(this)
                .closest("tr")
                .find("td[headers=id]")
                .text(),
        });
    });

    $(document).delegate(".table_structure .delete_row", "click", function() {
        var row_id = $(this).closest("tr");

        // Czy na pewno?
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
                    // Usuń row
                    row_id.fadeOut("slow");
                    row_id.css({ background: "#FFF4BA" });

                    // Odśwież stronę
                    refresh_blocks("admincontent", true);
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });

    $(document).delegate("#form_charge_wallet", "submit", function(e) {
        e.preventDefault();
        loader.show();
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
                    showWarnings($("#form_charge_wallet"), jsonObj.warnings);
                } else if (jsonObj.return_id === "charged") {
                    // Zmień stan portfela
                    getnset_template(
                        row_id.children("td[headers=wallet]"),
                        "admin_user_wallet",
                        true,
                        {
                            uid: $("#form_charge_wallet input[name=uid]").val(),
                        },
                        function() {
                            // Podświetl row
                            row_id.children("td[headers=wallet]").effect("highlight", 1000);
                        }
                    );

                    // Ukryj i wyczyść action box
                    action_box.hide();
                    $("#action_box_wraper_td").html("");
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });

    $(document).delegate("#form_change_password", "submit", function(e) {
        e.preventDefault();
        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("jsonhttp_admin.php"),
            data: $(this).serialize() + "&action=change_password",
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
                    showWarnings($("#form_charge_wallet"), jsonObj.warnings);
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
        $.ajax({
            type: "POST",
            url: buildUrl("jsonhttp_admin.php"),
            data: $(this).serialize() + "&action=user_edit",
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
                    showWarnings($("#form_user_edit"), jsonObj.warnings);
                } else if (jsonObj.return_id === "ok") {
                    // Ukryj i wyczyść action box
                    action_box.hide();
                    $("#action_box_wraper_td").html("");

                    // Odśwież stronę
                    refresh_blocks("admincontent", true);
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: handleErrorResponse,
        });
    });
})();
