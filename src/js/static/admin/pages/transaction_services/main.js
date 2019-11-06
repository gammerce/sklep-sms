$(document).delegate(".table-structure .edit_row", "click", function() {
    show_action_box(currentPage, "transaction_service_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

$(document).delegate("#form_transaction_service_edit", "submit", function(e) {
    e.preventDefault();

    loader.show();

    var transactionServiceId = $(this).find("[name=id]");

    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/transaction_services/" + transactionServiceId),
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
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
