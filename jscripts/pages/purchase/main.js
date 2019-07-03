// Wysłanie formularza zakupu
$(document).delegate("#form_purchase", "submit", function(e) {
    e.preventDefault();
});

$(document).delegate("#go_to_payment", "click", function() {
    if (loader.blocked) return;

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp.php"),
        data: $("#form_purchase").serialize() + "&action=purchase_form_validate",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            $(".form_warning").remove(); // Usuniecie komunikatow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function(name, text) {
                    var id = $('#form_purchase [name="' + name + '"]:first');
                    id.parent().append(text);
                    id.effect("highlight", 1000);
                });
            } else if (jsonObj.return_id == "ok") {
                // Przechodzimy do płatności
                go_to_payment(jsonObj.data, jsonObj.sign);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            // Wyświetlenie zwróconego info
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
