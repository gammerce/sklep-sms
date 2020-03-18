import { go_to_payment } from "../../utils/utils";
import { json_parse } from "../../../general/stocks";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { window_info } from "../../../general/window";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

// Send purchase form
$(document).delegate("#form_purchase", "submit", function(e) {
    e.preventDefault();
});

$(document).delegate("#go_to_payment", "click", function() {
    if (loader.blocked) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/purchase"),
        data: $("#form_purchase").serialize(),
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
                showWarnings($("#form_purchase"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                go_to_payment(jsonObj.data, jsonObj.sign);
            }

            if (typeof jsonObj.length !== "undefined")
                infobox.show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
            else infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Show service long description
$(document).delegate("#show_service_desc", "click", function() {
    var serviceId = $("#form_purchase [name=service_id]").val();

    loader.show();
    $.ajax({
        type: "GET",
        url: buildUrl("/api/services/" + serviceId + "/long_description"),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            window_info.create("80%", "80%", content);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_purchase [name=price_id]", "change", function() {
    var form = $(this).closest("form");

    if ($(this).val().length) {
        form.find("#cost_wrapper").slideDown("slow");
    } else {
        form.find("#cost_wrapper").slideUp("slow");
        return;
    }

    var option = $(this).find("option:selected");
    var directBillingPrice = option.data("direct-billing-price");
    var transferPrice = option.data("transfer-price");
    var smsPrice = option.data("sms-price");

    toggleCost(form.find("#cost_direct_billing"), directBillingPrice);
    toggleCost(form.find("#cost_sms"), smsPrice);
    toggleCost(form.find("#cost_transfer"), transferPrice);
});

function toggleCost(node, price) {
    if (price) {
        node.text(price);
        node.parent().show();
    } else {
        node.text("");
        node.parent().hide();
    }
}
