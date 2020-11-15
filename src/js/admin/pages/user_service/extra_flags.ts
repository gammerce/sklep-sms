import { hideAndDisable, restRequest, showAndEnable } from "../../../general/global";
import { service_module_act_can } from "../../../general/stocks";
import { get_type_name } from "../../../general/extra_flags";

// --------------------------------------------------------------------------------------------
// Dodanie usługi graczowi
//

// Zmiana typu usługi
$(document).delegate("#form_user_service_add [name=type]", "change", function () {
    const module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    const currentType = $(this).val() as string;

    hideAndDisable(module.find("#type_nick"));
    hideAndDisable(module.find("#type_ip"));
    hideAndDisable(module.find("#type_sid"));
    hideAndDisable(module.find("#type_password"));
    showAndEnable(module.find("#type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(module.find("#type_password"));
    }
});

// --------------------------------------------------------------------------------------------
// Edycja usługi gracza
//

// Zmiana usługi przy edycji
$(document).delegate("#form_user_service_edit [name=service_id]", "change", function () {
    const module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    const serviceId = $(this).val() as string;

    if (!serviceId.length) {
        module.find("[name=server_id]").children().not("[value='']").remove();
        return;
    }

    restRequest(
        "POST",
        `/api/admin/services/${serviceId}/actions/servers_for_service`,
        {
            server_id: module.find("[name=server_id]").val(),
        },
        function (html) {
            module.find("[name=server_id]").html(html);
        }
    );
});

$(document).delegate("#form_user_service_add [name=forever]", "change", function () {
    const module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    if ($(this).prop("checked")) {
        module.find("[name=quantity]").prop("disabled", true);
    } else {
        module.find("[name=quantity]").prop("disabled", false);
    }
});

$(document).delegate("#form_user_service_edit [name=forever]", "change", function () {
    const module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    if ($(this).prop("checked")) {
        module.find("[name=expire]").prop("disabled", true);
    } else {
        module.find("[name=expire]").prop("disabled", false);
    }
});

// Zmiana typu usługi
$(document).delegate("#form_user_service_edit [name=type]", "change", function () {
    const module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    const currentType = $(this).val() as string;

    hideAndDisable(module.find("#type_nick"));
    hideAndDisable(module.find("#type_ip"));
    hideAndDisable(module.find("#type_sid"));
    hideAndDisable(module.find("#type_password"));
    showAndEnable(module.find("#type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(module.find("#type_password"));
    }
});
