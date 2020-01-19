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

// Zmiana serwera
$(document).delegate("#form_purchase [name=server]", "change", function() {
    var form = $(this).closest("form");

    form.find("#cost_wrapper").slideUp();
    if ($(this).val() == "") {
        form.find("[name=quantity]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = form.find("[name=service]").val();

    restRequest(
        "POST",
        "/api/services/" + serviceId + "/actions/prices_for_server",
        {
            server: $(this).val(),
        },
        function(html) {
            form.find("[name=quantity]").html(html);
        }
    );
});
