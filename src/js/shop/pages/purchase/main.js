import { goToPayment } from "../../utils/utils";
import { json_parse } from "../../../general/stocks";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { window_info } from "../../../general/window";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

// Send purchase form
$(document).delegate("#form_purchase", "submit", function(e) {
    e.preventDefault();

    if (loader.blocked) {
        return;
    }

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/purchases"),
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
                goToPayment(jsonObj.transaction_id);
            }

            if (jsonObj.positive) {
                infobox.show_info(jsonObj.text, jsonObj.positive, 8000);
            } else {
                infobox.show_info(jsonObj.text, jsonObj.positive);
            }
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

$(document).delegate("#form_purchase [name=quantity]", "change", function() {
    var form = $(this).closest("form");

    if ($(this).val().length) {
        form.find("#cost_wrapper").slideDown("slow");
    } else {
        form.find("#cost_wrapper").slideUp("slow");
        return;
    }

    var option = $(this).find("option:selected");
    var directBillingDiscount = option.data("direct-billing-discount");
    var directBillingPrice = option.data("direct-billing-price");
    var transferDiscount = option.data("transfer-discount");
    var transferPrice = option.data("transfer-price");
    var smsDiscount = option.data("sms-discount");
    var smsPrice = option.data("sms-price");

    toggleCost(form.find("#cost_direct_billing"), directBillingPrice, directBillingDiscount);
    toggleCost(form.find("#cost_sms"), smsPrice, smsDiscount);
    toggleCost(form.find("#cost_transfer"), transferPrice, transferDiscount);
});

function toggleCost(node, price, discount) {
    node.text("");
    node.parent()
        .find(".discount")
        .text("");
    node.parent().hide();

    if (price) {
        node.text(price);

        if (discount) {
            node.parent()
                .find(".discount")
                .text(`-${discount}%`);
        }

        node.parent().show();
    }
}
