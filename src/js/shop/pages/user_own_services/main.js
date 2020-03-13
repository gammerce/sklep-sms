import {go_to_payment, refresh_blocks} from "../../utils/utils";
import {json_parse} from "../../../general/stocks";
import {buildUrl, removeFormWarnings, restRequest, showWarnings} from "../../../general/global";
import {loader} from "../../../general/loader";
import {handleErrorResponse, infobox, sthWentWrong} from "../../../general/infobox";

// Click on edit service
$(document).delegate("#user_own_services .edit_row", "click", function() {
    var row = $(this).closest("form");
    var card = row.closest(".card");
    var userServiceId = row.data("row");

    restRequest("GET", "/api/user_services/" + userServiceId + "/edit_form", {}, function(html) {
        row.html(html);
        card.addClass("active");

        row.find(".cancel").click(function() {
            var userServiceId = row.data("row");

            restRequest("GET", "/api/user_services/" + userServiceId + "/brick", {}, function(
                html
            ) {
                row.html(html);
                card.removeClass("active");
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
