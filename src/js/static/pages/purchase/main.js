// Wysłanie formularza zakupu
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

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_purchase"), jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
                // Przechodzimy do płatności
                go_to_payment(jsonObj.data, jsonObj.sign);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            if (typeof jsonObj.length !== "undefined")
                infobox.show_info(jsonObj.text, jsonObj.positive, jsonObj.length);
            else infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Pokaż pełny opis usługi
$(document).delegate("#show_service_desc", "click", function() {
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp.php"),
        data: {
            action: "get_service_long_description",
            service: $("#form_purchase [name=service]").val(),
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            window_info.create("80%", "80%", content);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
