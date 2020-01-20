jQuery(document).ready(function($) {
    // So as no option is selected when somebody returned to the previous page
    $("#form_purchase")
        .find("#purchase_value")
        .val("0");
});

$(document).delegate("#form_purchase input[name=type]", "change", function() {
    var form = $(this).closest("form");

    form.find("#type_nick").hide();
    form.find("#type_ip").hide();
    form.find("#type_sid").hide();
    form.find("#type_password").hide();
    form.find("#type_" + get_type_name($(this).val())).show();
    if ($(this).val() == "1" || $(this).val() == "2") form.find("#type_password").show();
});

$(document).delegate("#form_purchase [name=server_id]", "change", function() {
    var form = $(this).closest("form");

    form.find("#cost_wrapper").slideUp();
    if ($(this).val() == "") {
        form.find("[name=price_id]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        "/api/services/" + serviceId + "/actions/prices_for_server",
        {
            server: $(this).val(),
        },
        function(html) {
            form.find("[name=price_id]").html(html);
        }
    );
});
