// --------------------------------------------------------------------------------------------
// Dodanie usługi graczowi
//

// Zmiana typu usługi
$(document).delegate("#form_user_service_add [name=type]", "change", function() {
    var module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    var currentType = $(this).val();

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
$(document).delegate("#form_user_service_edit [name=service_id]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    if (!$(this).val().length) {
        module
            .find("[name=server_id]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = $(this).val();

    restRequest(
        "POST",
        "/api/services/" + serviceId + "/actions/servers_for_service",
        {
            server_id: module.find("[name=server_id]").val(),
        },
        function(html) {
            module.find("[name=server_id]").html(html);
        }
    );
});

$(document).delegate("#form_user_service_add [name=forever]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    if ($(this).prop("checked")) module.find("[name=quantity]").prop("disabled", true);
    else module.find("[name=quantity]").prop("disabled", false);
});

$(document).delegate("#form_user_service_edit [name=forever]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    if ($(this).prop("checked")) module.find("[name=expire]").prop("disabled", true);
    else module.find("[name=expire]").prop("disabled", false);
});

// Zmiana typu usługi
$(document).delegate("#form_user_service_edit [name=type]", "change", function() {
    var module = service_module_act_can("extra_flags", $(this));
    if (!module) {
        return;
    }

    var currentType = $(this).val();

    hideAndDisable(module.find("#type_nick"));
    hideAndDisable(module.find("#type_ip"));
    hideAndDisable(module.find("#type_sid"));
    hideAndDisable(module.find("#type_password"));
    showAndEnable(module.find("#type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(module.find("#type_password"));
    }
});
