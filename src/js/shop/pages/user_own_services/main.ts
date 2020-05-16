import { goToPayment, refreshBlocks } from "../../utils/utils";
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

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings(that, content.warnings);
            } else if (content.return_id === "ok") {
                refreshBlocks(`content:${window.currentPage}`);
            } else if (content.return_id === "payment") {
                goToPayment(content.transaction_id);
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
