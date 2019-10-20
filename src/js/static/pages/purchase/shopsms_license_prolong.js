function ss_prolong_set_cost() {
    if (
        $(".shopsms_license_prolong_purchase [name=amount]").val() == "" ||
        $(".shopsms_license_prolong_purchase [name=identifier]").val() == ""
    ) {
        $(".shopsms_license_prolong_purchase #cost").html(lang["none"]);
        return;
    }

    var tmp_data = $("#form_purchase").serializeArray();
    var data = {}; // Musi byc, inaczej nie dziala
    $.each(tmp_data, function(index, element) {
        data[element.name] = element.value;
    });
    data["service_action"] = "get_cost";

    // Wywolujemy skrypt php, ktory ustali koszt
    fetch_data("service_action_execute", false, data, function(html) {
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
