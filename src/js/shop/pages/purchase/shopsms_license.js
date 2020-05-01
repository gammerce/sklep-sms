import { restRequest } from "../../../general/global";

function engine_toggle(element) {
    if (element.val() == "1") {
        element.val("0");
        set_cost();
        return false;
    } else {
        element.val("1");
        set_cost();
        return true;
    }
}

function set_cost() {
    const form = $(".shopsms_license_purchase");

    if (!form.find("[name=amount]").val().length) {
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

$(document).ready(function($) {
    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    $(".shopsms_license_purchase [name=platform_sourcemod]").val("0");
    $(".shopsms_license_purchase [name=platform_amxmodx]").val("0");
    set_cost();
});

// Zaznaczamy jakas gre
$(document).delegate(".shopsms_license_purchase .engine", "click", function() {
    const form = $(this).closest("form");

    let toggle_value = false;
    if ($(this).hasClass("amxx")) {
        toggle_value = engine_toggle(form.find("[name=platform_amxmodx]"));
    } else if ($(this).hasClass("sm")) {
        toggle_value = engine_toggle(form.find("[name=platform_sourcemod]"));
    }

    // Usuwamy / dodajemy klase active
    if (toggle_value) {
        $(this).addClass("active");
    } else {
        $(this).removeClass("active");
    }
});

// Zmiana ilosci dni
$(document).delegate(".shopsms_license_purchase [name=amount]", "change", function() {
    set_cost();
});

// Kliknięcie przeładowania
$(document).delegate(".shopsms_license_purchase #cost .reload", "click", function() {
    set_cost();
});
