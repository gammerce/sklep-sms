import { restRequest } from "../../../general/global";

function set_cost() {
    const form = $(".shopsms_license_purchase");

    const amount = form.find("[name=amount]").val() as string;
    if (!amount.length) {
        $("#cost .price").html("0.00");
        return;
    }

    const serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        `/api/services/${serviceId}/actions/get_cost`,
        $(form).serialize(),
        function (html) {
            $("#cost .price").html(html);
        }
    );
}

$(document).ready(set_cost);

// Zaznaczamy jakas platformę
$(document).delegate(".shopsms_license_purchase [name='platforms[]']", "click", set_cost);

// Zmiana ilosci dni
$(document).delegate(".shopsms_license_purchase [name=amount]", "change", set_cost);

// Kliknięcie przeładowania
$(document).delegate(".shopsms_license_purchase #cost .reload", "click", set_cost);
