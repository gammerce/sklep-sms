$(document).delegate("#pay_wallet", "click", function() {
    purchase_service("wallet");
});

$(document).delegate("#pay_transfer", "click", function() {
    purchase_service("transfer");
});

$(document).delegate("#pay_direct_billing", "click", function() {
    purchase_service("direct_billing");
});

$(document).delegate("#pay_sms", "click", function() {
    if ($("#sms_details").css("display") === "none") {
        $("#sms_details").slideDown("slow");
    } else {
        purchase_service("sms");
    }
});

$(document).delegate("#pay_service_code", "click", function() {
    $("#sms_details").slideUp();
    purchase_service("service_code");
});

function redirectToTransferWithPost(jsonObj) {
    var form = $("<form>", {
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

function redirectToTransferWithGet(jsonObj) {
    var url = jsonObj.data.url;
    delete jsonObj.data.url;
    var urlWithPath = url + "?" + $.param(jsonObj.data);
    window.location.href = urlWithPath;
}

function purchase_service(method) {
    if (loader.blocked) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/payment"),
        data: {
            method: method,
            sms_code: $("#sms_code").val(),
            service_code: $("#service_code").val(),
            purchase_data: $("#payment [name=purchase_data]").val(),
            purchase_sign: $("#payment [name=purchase_sign]").val(),
        },
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
                showWarnings($("#payment"), jsonObj.warnings);
            } else if (jsonObj.return_id === "purchased") {
                // Update content window with purchase details
                restRequest("GET", "/api/purchases/" + jsonObj.bsid, {}, function(message) {
                    $("#content").html(message);
                });

                // Refresh wallet
                refresh_blocks("wallet", function() {
                    $("#wallet").effect("highlight", "slow");
                });
            } else if (jsonObj.return_id === "transfer") {
                var method = jsonObj.data.method;
                delete jsonObj.data.method;

                if (method === "GET") {
                    redirectToTransferWithGet(jsonObj);
                } else if (method === "POST") {
                    redirectToTransferWithPost(jsonObj);
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
