// Click on edit service
$(document).delegate("#user_own_services .edit_row", "click", function() {
    var rowId = $(this).parents("form:first");
    var userServiceId = rowId.data("row");

    rest_request("GET", "/api/user_services/" + userServiceId + "/edit_form", {}, function(html) {
        rowId.html(html);
        rowId.parents(".brick:first").addClass("active");

        // Dodajemy event, aby powróciło do poprzedniego stanu po kliknięciu "Anuluj"
        rowId.find(".cancel").click({ row_id: rowId }, function(e) {
            var rowId = e.data.row_id;
            var userServiceId = rowId.data("row");

            rest_request("GET", "/api/user_services/" + userServiceId + "/brick", {}, function(
                html
            ) {
                rowId.html(html);
                rowId.parents(".brick:first").removeClass("active");
            });
        });
    });
});

// Edit service
$(document).delegate("#user_own_services .row", "submit", function(e) {
    e.preventDefault();

    var that = $(this);
    var userServiceId = that.data("row");

    loader.show();

    $.ajax({
        type: "PUT",
        url: buildUrl("/api/user_services/" + userServiceId),
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
                showWarnings(that, jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                refresh_blocks("content");
            } else if (jsonObj.return_id === "payment") {
                go_to_payment(jsonObj.data, jsonObj.sign);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
