import { restRequest } from "../../../general/global";

function ss_prolong_set_cost() {
    if (
        $(".shopsms_license_prolong_purchase [name=amount]").val() == "" ||
        $(".shopsms_license_prolong_purchase [name=identifier]").val() == ""
    ) {
        $(".shopsms_license_prolong_purchase #cost").html(lang["none"]);
        return;
    }

    var tmp_data = $("#form_purchase").serializeArray();
    var data = {};
    $.each(tmp_data, function(index, element) {
        data[element.name] = element.value;
    });

    var serviceId = $("#form_purchase [name=service_id]").val();

    restRequest("POST", "/api/services/" + serviceId + "/actions/get_cost", data, function(html) {
        $(".shopsms_license_prolong_purchase #cost").html(html);
    });
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
$(document).delegate(".shopsms_license_prolong_purchase #cost_reload", "click", function() {
    $(this)
        .closest("#cost")
        .html("...");
    ss_prolong_set_cost();
});
