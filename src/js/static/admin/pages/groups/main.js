$(document).delegate("#group_button_add", "click", function() {
    show_action_box(currentPage, "group_add");
});

$(document).delegate(".table-structure .edit_row", "click", function() {
    show_action_box(currentPage, "group_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

// Delete group
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var groupId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/groups/" + groupId),
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

// Add group
$(document).delegate("#form_group_add", "submit", function(e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/groups"),
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

            if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit group
$(document).delegate("#form_group_edit", "submit", function(e) {
    e.preventDefault();

    var groupId = $(this)
        .find("[name=id]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/groups/" + groupId),
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

            if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
