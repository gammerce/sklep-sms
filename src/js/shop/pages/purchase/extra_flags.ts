import { get_type_name } from "../../../general/extra_flags";
import { hideAndDisable, restRequest, showAndEnable } from "../../../general/global";

$(document).ready(function($) {
    // So as no option is selected when somebody returned to the previous page
    $("#form_purchase")
        .find("#purchase_value")
        .val("0");
});

$(document).delegate("#form_purchase input[name=type]", "change", function() {
    var form = $(this).closest("form");
    var currentType = $(this).val() as string;

    hideAndDisable(form.find("#type_nick"));
    hideAndDisable(form.find("#type_ip"));
    hideAndDisable(form.find("#type_sid"));
    hideAndDisable(form.find("#type_password"));
    showAndEnable(form.find("#type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(form.find("#type_password"));
    }
});

$(document).delegate("#form_purchase [name=server_id]", "change", function() {
    var form = $(this).closest("form");

    form.find("#cost_box").slideUp();
    if ($(this).val() == "") {
        form.find("[name=quantity]")
            .children()
            .not("[value='']")
            .remove();
        return;
    }

    var serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        `/api/services/${serviceId}/actions/prices_for_server`,
        {
            server_id: $(this).val(),
        },
        function(html) {
            form.find("[name=quantity]").html(html);
        }
    );
});
