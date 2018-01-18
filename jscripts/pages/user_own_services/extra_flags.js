//Zmiana typu usługi
$(document).delegate("#user_own_services .row [name=type]", "change", function () {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this))))
        return;

    module.find(".type_nick").hide();
    module.find(".type_ip").hide();
    module.find(".type_sid").hide();
    module.find(".type_password").hide();
    module.find(".type_" + get_type_name($(this).val())).show();

    if ($(this).val() == "1" || $(this).val() == "2")
        module.find(".type_password").show();
});