import { service_module_act_can } from "../../../general/stocks";
import { get_type_name } from "../../../general/extra_flags";
import { hideAndDisable, showAndEnable } from "../../../general/global";

$(document).delegate("#form_service_take_over [name=type]", "change", function() {
    var module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    var currentType = $(this).val();

    hideAndDisable(module.find("[data-type='nick']"));
    hideAndDisable(module.find("[data-type='ip']"));
    hideAndDisable(module.find("[data-type='sid']"));
    hideAndDisable(module.find("[data-type='password']"));
    showAndEnable(module.find("[data-type='" + get_type_name(currentType) + "']"));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(module.find("[data-type='password']"));
    }
});

$(document).delegate("#form_service_take_over [name=payment_method]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    // TODO Allow other payment methods

    module.find("[data-name='payment_id']").hide();
    if ($(this).val() == "sms" || $(this).val() == "transfer") {
        module.find("[data-name='payment_id']").show();
    }
});
