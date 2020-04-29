import { goToPayment, refreshBlocks } from "../../utils/utils";
import { json_parse } from "../../../general/stocks";
import { buildUrl, removeFormWarnings, restRequest, showWarnings } from "../../../general/global";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

// Click on edit service
$(document).delegate("#user_own_services .edit_row", "click", function() {
    const row = $(this).closest("form");
    const card = row.closest(".card");
    const userServiceId = row.data("row");

    restRequest("GET", `/api/user_services/${userServiceId}/edit_form`, {}, function(html) {
        row.html(html);
        card.addClass("is-active");

        row.find(".cancel").click(function() {
            const userServiceId = row.data("row");

            restRequest("GET", `/api/user_services/${userServiceId}/brick`, {}, function(html) {
                row.html(html);
                card.removeClass("is-active");
            });
        });
    });
});

// Edit service
$(document).delegate("#user_own_services .row", "submit", function(e) {
    e.preventDefault();

    const that = $(this);
    const userServiceId = that.data("row");

    loader.show();

    $.ajax({
        type: "PUT",
        url: buildUrl(`/api/user_services/${userServiceId}`),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            const jsonObj = json_parse(content);
            if (!jsonObj || !jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings(that, jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                refreshBlocks("content");
            } else if (jsonObj.return_id === "payment") {
                goToPayment(jsonObj.transaction_id);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
