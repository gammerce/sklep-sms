$(document).ready(function($) {
    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    $(".shopsms_license_purchase input[name=platform_sourcemod]").val("0");
    $(".shopsms_license_purchase input[name=platform_amxmodx]").val("0");

    set_cost();
});

// Zaznaczamy jakas gre
$(document).delegate(".shopsms_license_purchase .engine", "click", function() {
    var form = $(this).closest("form");

    var toggle_value = false;
    if ($(this).hasClass("amxx")) {
        toggle_value = engine_toggle(form.find("input[name=platform_amxmodx]"));
    } else if ($(this).hasClass("sm")) {
        toggle_value = engine_toggle(form.find("input[name=platform_sourcemod]"));
    }

    // Usuwamy / dodajemy klase active
    if (toggle_value) {
        $(this).addClass("active");
    } else {
        $(this).removeClass("active");
    }
});

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
    if (!$(".shopsms_license_purchase [name=amount]").val().length) {
        $(".shopsms_license_purchase #cost").html(lang["none"]);
        return;
    }

    var tmpData = $("#form_purchase").serializeArray();
    var data = {};
    $.each(tmpData, function(index, element) {
        data[element.name] = element.value;
    });

    var serviceId = $("#form_purchase [name=service]").val();

    restRequest("POST", "/api/services/" + serviceId + "/actions/get_cost", data, function(html) {
        $(".shopsms_license_purchase #cost").html(html);
    });
}

// Zmiana ilosci dni
$(document).delegate(".shopsms_license_purchase [name=amount]", "change", function() {
    set_cost();
});

// Kliknięcie przeładowania
$(document).delegate(".shopsms_license_purchase #cost_reload", "click", function() {
    $(this)
        .closest("#cost")
        .html("...");
    set_cost();
});
