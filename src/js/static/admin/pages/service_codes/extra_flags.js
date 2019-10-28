// Zmiana serwera
$(document).delegate("#form_service_code_add [name=server]", "change", function() {
    var module;
    if (!(module = service_module_act_can("extra_flags", $(this)))) return;

    if (!$(this).val().length) {
        module
            .find("[name=amount]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = module
        .closest("form")
        .find("[name=service]")
        .val();

    rest_request(
        "POST",
        "/api/service/" + serviceId + "/actions/tariffs_for_server",
        {
            server: $(this).val(),
        },
        function(html) {
            module.find("[name=amount]").html(html);
        }
    );
});
