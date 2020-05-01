import { restRequest } from "../../../general/global";

function ss_prolong_set_cost() {
    const form = $(".shopsms_license_prolong_purchase");

    if (form.find("[name=amount]").val() == "" || form.find("[name=identifier]").val() == "") {
        $("#cost .price").html("0.00");
        return;
    }

    const serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        `/api/services/${serviceId}/actions/get_cost`,
        $(form).serialize(),
        function(html) {
            $("#cost .price").html(html);
        }
    );
}

// Zmiana ilosci dni
$(document).delegate(".shopsms_license_prolong_purchase [name=amount]", "change", function() {
    ss_prolong_set_cost();
});

// Zmiana identyfikatora licencji
$(document).delegate(".shopsms_license_prolong_purchase [name=identifier]", "change", function() {
    ss_prolong_set_cost();
});

// Kliknięcie przeładowania
$(document).delegate(".shopsms_license_prolong_purchase #cost .reload", "click", function() {
    ss_prolong_set_cost();
});
