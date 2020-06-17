import { goToPayment } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { window_info } from "../../../general/window";
import { buildUrl, hide, removeFormWarnings, show, showWarnings } from "../../../general/global";

// Send purchase form
$(document).delegate("#form_purchase", "submit", function(e) {
    e.preventDefault();

    if (loader.blocked) {
        return;
    }

    const that = this;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/purchases"),
        data: $(this).serialize(),
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($(that), content.warnings);
            } else if (content.return_id === "ok") {
                goToPayment(content.transaction_id);
            }

            if (content.positive) {
                infobox.showInfo(content.text, content.positive, 8000);
            } else {
                infobox.showInfo(content.text, content.positive);
            }
        },
        error: handleErrorResponse,
    });
});

// Show service long description
$(document).delegate("#show_service_desc", "click", function() {
    const serviceId = $("#form_purchase [name=service_id]").val();

    loader.show();
    $.ajax({
        type: "GET",
        url: buildUrl(`/api/services/${serviceId}/long_description`),
        complete() {
            loader.hide();
        },
        success(content) {
            window_info.create("80%", "80%", content);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_purchase [name=quantity]", "change", function() {
    const form = $(this).closest("form");
    const quantity = $(this).val() as string;

    if (quantity.length) {
        show(form.find("#cost_box"));
    } else {
        hide(form.find("#cost_box"));
        return;
    }

    const option = $(this).find("option:selected");
    const directBillingDiscount = option.data("direct-billing-discount");
    const directBillingPrice = option.data("direct-billing-price");
    const transferDiscount = option.data("transfer-discount");
    const transferPrice = option.data("transfer-price");
    const smsDiscount = option.data("sms-discount");
    const smsPrice = option.data("sms-price");

    toggleCost(form.find("#cost_direct_billing"), directBillingPrice, directBillingDiscount);
    toggleCost(form.find("#cost_sms"), smsPrice, smsDiscount);
    toggleCost(form.find("#cost_transfer"), transferPrice, transferDiscount);
});

function toggleCost(node, price, discount) {
    const priceNode = node.find(".price");
    const discountNode = node.find(".discount");

    priceNode.text("");
    discountNode.text("");

    if (price) {
        priceNode.text(price);

        if (discount) {
            discountNode.text(`-${discount}%`);
        }

        show(node);
    } else {
        hide(node);
    }
}
