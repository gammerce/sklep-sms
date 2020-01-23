// --------------------------------------------------------------------------------------------
// Dodanie usługi graczowi
//

// Zmiana typu usługi
$(document).delegate("#form_user_service_add [name=type]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    module.find("#type_nick").hide();
    module.find("#type_ip").hide();
    module.find("#type_sid").hide();
    module.find("#type_password").hide();
    module.find("#type_" + get_type_name($(this).val())).show();

    if ($(this).val() == "1" || $(this).val() == "2") module.find("#type_password").show();
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

// Ustawienie na zawsze przy edycji
$(document).delegate("#form_user_service_edit [name=forever]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    if ($(this).prop("checked")) module.find("[name=expire]").prop("disabled", true);
    else module.find("[name=expire]").prop("disabled", false);
});

// Zmiana typu usługi
$(document).delegate("#form_user_service_edit [name=type]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    module.find("#type_nick").hide();
    module.find("#type_ip").hide();
    module.find("#type_sid").hide();
    module.find("#type_password").hide();
    module.find("#type_" + get_type_name($(this).val())).show();

    if ($(this).val() == "1" || $(this).val() == "2") module.find("#type_password").show();
});
