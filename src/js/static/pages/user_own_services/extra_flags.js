$(document).delegate("#user_own_services .row [name=type]", "change", function() {
    var module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    var currentType = $(this).val();

    hideAndDisable(module.find(".type_nick"));
    hideAndDisable(module.find(".type_ip"));
    hideAndDisable(module.find(".type_sid"));
    hideAndDisable(module.find(".type_password"));
    showAndEnable(module.find(".type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(module.find(".type_password"));
    }
});
