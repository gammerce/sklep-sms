import { refreshBlocks } from "../../utils/utils";
import { loader } from "../../../general/loader";
import {
    buildUrl,
    hide,
    isShown,
    removeFormWarnings,
    restRequest,
    show,
    showWarnings,
} from "../../../general/global";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { json_parse } from "../../../general/stocks";

$(document).delegate("#pay_wallet", "click", function() {
    purchaseService("wallet");
});

$(document).delegate("#pay_transfer", "click", function() {
    purchaseService("transfer");
});

$(document).delegate("#pay_direct_billing", "click", function() {
    purchaseService("direct_billing");
});

$(document).delegate("#pay_sms", "click", function() {
    const smsDetails = $("#sms_details");

    if (!isShown(smsDetails)) {
        show(smsDetails);
    } else {
        purchaseService("sms");
    }
});

function redirectToExternalWithPost(jsonObj) {
    const form = $("<form>", {
        action: jsonObj.data.url,
        method: "POST",
    });

    $.each(jsonObj.data, function(key, value) {
        if (key === "url") return true; // continue

        form.append(
            $("<input>", {
                type: "hidden",
                name: key,
                value: value,
            })
        );
    });

    // Bez tego nie dziala pod firefoxem
    $("body").append(form);

    // Wysyłamy formularz zakupu
    form.submit();
}

function redirectToExternalWithGet(jsonObj) {
    const url = jsonObj.data.url;
    delete jsonObj.data.url;

    window.location.href = url + "?" + $.param(jsonObj.data);
}

function purchaseService(method) {
    if (loader.blocked) {
        return;
    }

    const queryParams = new URLSearchParams(window.location.search);
    const transactionId = encodeURIComponent(queryParams.get("tid"));

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl(`/api/payment/${transactionId}`),
        data: {
            method: method,
            sms_code: $("#sms_code").val(),
        },
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            const jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#payment"), jsonObj.warnings);
            } else if (jsonObj.return_id === "purchased") {
                // Update content window with purchase details
                restRequest("GET", `/api/purchases/${jsonObj.bsid}`, {}, function(message) {
                    $("#page-content").html(message);
                });

                // Refresh wallet
                refreshBlocks("wallet", function() {
                    const wallet = $("#wallet");
                    if (wallet.effect) {
                        wallet.effect("highlight", "slow");
                    }
                });
            } else if (jsonObj.return_id === "external") {
                const method = jsonObj.data.method;
                delete jsonObj.data.method;

                if (method === "GET") {
                    redirectToExternalWithGet(jsonObj);
                } else if (method === "POST") {
                    redirectToExternalWithPost(jsonObj);
                } else {
                    console.error("Invalid method specified by PaymentModule");
                    sthWentWrong();
                    return;
                }
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
}
