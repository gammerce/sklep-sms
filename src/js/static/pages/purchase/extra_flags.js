jQuery(document).ready(function($) {
    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    $("#form_purchase")
        .find("#purchase_value")
        .val("0");
});

// Zmiana typu zakupu
$(document).delegate("#form_purchase input[name=type]", "change", function() {
    var form = $(this).closest("form");

    form.find("#type_nick").hide();
    form.find("#type_ip").hide();
    form.find("#type_sid").hide();
    form.find("#type_password").hide();
    form.find("#type_" + get_type_name($(this).val())).show();
    if ($(this).val() == "1" || $(this).val() == "2") form.find("#type_password").show();
});

// Zmiana wartości zakupu
$(document).delegate("#form_purchase [name=value]", "change", function() {
    var form = $(this).closest("form");

    if ($(this).val().length) form.find("#cost_wraper").slideDown("slow");
    else {
        form.find("#cost_wraper").slideUp("slow");
        return;
    }

    var values = $(this)
        .val()
        .split(";");
    form.find("#cost_transfer").text(parseFloat(values[0]).toFixed(2));
    if (values[1] != "0") {
        form.find("#cost_sms").text(parseFloat(values[1]).toFixed(2));
        form.find("#currency_sms").show();
    } else {
        form.find("#cost_sms").text(lang["none"]);
        form.find("#currency_sms").hide();
    }
});

// Zmiana serwera
$(document).delegate("#form_purchase [name=server]", "change", function() {
    var form = $(this).closest("form");

    form.find("#cost_wraper").slideUp();
    if ($(this).val() == "") {
        form.find("[name=value]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = form.find("[name=service]").val();

    rest_request(
        "POST",
        "/api/service/" + serviceId + "/actions/tariffs_for_server",
        {
            server: $(this).val(),
        },
        function(html) {
            form.find("[name=value]").html(html);
        }
    );
});
