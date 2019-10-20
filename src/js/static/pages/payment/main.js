//Kliknięcie na płacenie portfelem
$(document).delegate("#pay_wallet", "click", function() {
    $("#sms_details").slideUp();
    purchase_service("wallet");
});

// Kliknięcie na płacenie przelewem
$(document).delegate("#pay_transfer", "click", function() {
    $("#sms_details").slideUp();
    purchase_service("transfer");
});

// Kliknięcie na płacenie smsem
$(document).delegate("#pay_sms", "click", function() {
    if ($("#sms_details").css("display") === "none") $("#sms_details").slideDown("slow");
    else purchase_service("sms");
});

// Kliknięcie na płacenie kodem
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
        url: buildUrl("jsonhttp.php"),
        data: {
            action: "payment_form_validate",
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

            var jsonObj;
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#payment"), jsonObj.warnings);
            } else if (jsonObj.return_id == "purchased") {
                // Zmiana zawartosci okienka content na info o zakupie
                fetch_data("get_purchase_info", false, { purchase_id: jsonObj.bsid }, function(
                    message
                ) {
                    $("#content").html(message);
                });

                // Odswieżenie stanu portfela
                refresh_blocks("wallet", false, function() {
                    $("#wallet").effect("highlight", "slow");
                });
            } else if (jsonObj.return_id == "transfer") {
                var method = jsonObj.data.method;
                delete jsonObj.data.method;

                if (method === "GET") {
                    redirectToTransferWithGet(jsonObj);
                } else if (method === "POST") {
                    redirectToTransferWithPost(jsonObj);
                } else {
                    console.error("Invalid method specified by PaymentModule");
                    infobox.show_info(lang["sth_went_wrong"], false);
                    return;
                }
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
}
