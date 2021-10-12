import { get_type_name } from "../../../general/extra_flags";
import { hide, hideAndDisable, restRequest, showAndEnable } from "../../../general/global";

$(document).ready(function ($) {
    const form = $("#form_purchase");
    // So as no option is selected when somebody returned to the previous page
    form.find("#purchase_value").val("0");
    loadQuantityOptions(form);
});

$(document).delegate("#form_purchase input[name=type]", "change", function () {
    const form = $(this).closest("form");
    const currentType = $(this).val() as string;

    hideAndDisable(form.find("#type_nick"));
    hideAndDisable(form.find("#type_ip"));
    hideAndDisable(form.find("#type_sid"));
    hideAndDisable(form.find("#type_password"));
    showAndEnable(form.find("#type_" + get_type_name(currentType)));

    if (currentType == "1" || currentType == "2") {
        showAndEnable(form.find("#type_password"));
    }
});

$(document).delegate("#form_purchase [name=server_id]", "change", function () {
    const form = $(this).closest("form");
    loadQuantityOptions(form);
});

function loadQuantityOptions(form: JQuery) {
    const serviceId = form.find("[name=service_id]").val();
    const serverId = form.find("[name=server_id]").val();

    hide(form.find("#cost_box"));

    if (serverId == "") {
        form.find("[name=quantity]").children().not("[value='']").remove();
        return;
    }

    restRequest(
        "POST",
        `/api/services/${serviceId}/actions/prices_for_server`,
        {
            server_id: serverId,
        },
        function (html) {
            form.find("[name=quantity]").html(html);
        }
    );
}
