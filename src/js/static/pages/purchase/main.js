// Send purchase form
$(document).delegate("#form_purchase", "submit", function(e) {
    e.preventDefault();
});

$(document).delegate("#go_to_payment", "click", function() {
    if (loader.blocked) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/purchase/validation"),
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
    var serviceId = $("#form_purchase [name=service]").val();

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

    var transferPrice = $(this).data("transfer-price");
    var smsPrice = $(this).data("sms-price");

    if (transferPrice) {
        form.find("#cost_transfer").text(transferPrice);
        form.find("#cost_transfer")
            .parent()
            .show();
    } else {
        form.find("#cost_transfer").text("");
        form.find("#cost_transfer")
            .parent()
            .hide();
        form.find("#currency_transfer").hide();
    }

    if (smsPrice) {
        form.find("#cost_sms").text(smsPrice);
        form.find("#cost_sms")
            .parent()
            .show();
    } else {
        form.find("#cost_sms").text("");
        form.find("#cost_sms")
            .parent()
            .hide();
        form.find("#currency_sms").hide();
    }
});
