$(document).delegate("#form_service_take_over [name=type]", "change", function () {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this))))
        return;

    module.find("[data-type='nick']").hide();
    module.find("[data-type='ip']").hide();
    module.find("[data-type='sid']").hide();
    module.find("[data-type='nick']").hide();
    module.find("[data-type='password']").hide();
    module.find("[data-type='" + get_type_name($(this).val()) + "']").show();
    if ($(this).val() == "1" || $(this).val() == "2")
        module.find("[data-type='password']").show();
});

$(document).delegate("#form_service_take_over [name=payment]", "change", function () {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this))))
        return;

    module.find("[data-name='payment_id']").hide();
    if ($(this).val() == "sms" || $(this).val() == "transfer")
        module.find("[data-name='payment_id']").show();
});