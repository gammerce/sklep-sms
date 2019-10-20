// Zaznaczamy jakas gre
$(document).delegate(".shopsms_user_edit .engine", "click", function() {
    var toggle_value = false;
    if ($(this).hasClass("amxx"))
        toggle_value = engine_toggle(
            $(this)
                .parent()
                .find("input[name=platform_amxmodx]")
        );
    else if ($(this).hasClass("sm"))
        toggle_value = engine_toggle(
            $(this)
                .parent()
                .find("input[name=platform_sourcemod]")
        );

    ss_user_edit_set_cost($(this).closest("form"));

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
        return false;
    } else {
        element.val("1");
        return true;
    }
}

function regenerate_token(identifier, button) {
    var confirmed = confirm(
        "Czy na pewno chcesz ponownie wygenerować token dla licencji: " + identifier + "?"
    );

    if (confirmed) {
        loader.show();

        $.ajax({
            type: "POST",
            url: buildUrl("jsonhttp.php"),
            data: {
                action: "service_action_execute",
                service: "ss_license",
                service_action: "regenerate_token",
                identifier: identifier,
            },
            complete: function() {
                loader.hide();
            },
            success: function(content) {
                var response;

                if (!(response = json_parse(content))) {
                    return;
                }

                var box = $(button)
                    .closest(".row")
                    .find(".regenerated_token_box");
                box.addClass("is-active");
                box.find(".token_value").text(response.token);

                infobox.show_info("Token został zregenerowany", true);
            },
            error: function(error) {
                infobox.show_info(lang["ajax_error"], false);
                location.reload();
            },
        });
    }
}

function ss_user_edit_set_cost(form) {
    var tmp_data = form.serializeArray();
    var data = {}; // Musi byc, inaczej nie dziala
    $.each(tmp_data, function(index, element) {
        data[element.name] = element.value;
    });
    data["service_action"] = "get_cost_user_edit";
    data["user_service_id"] = form.data("row");

    // Wywolujemy skrypt php, ktory ustali koszt
    var tmp_form = form;
    fetch_data("service_action_execute", false, data, function(content) {
        var jsonObj;
        if (!(jsonObj = json_parse(content))) return;

        var cost = form.find("#cost");
        var cost_monthly = form.find("#cost_monthly");

        if (cost.html() != jsonObj.surcharge)
            // podswietlamy i zmieniamy zawartosc, gdy ta sie rzeczywiscie zmienila
            cost.html(jsonObj.surcharge).effect("highlight", 1000);
        if (cost_monthly.html() != jsonObj.cost_monthly)
            cost_monthly.html(jsonObj.cost_monthly).effect("highlight", 1000);
    });
}

// Kliknięcie przeładowania
$(document).delegate(".shopsms_user_edit #cost_reload", "click", function() {
    $(this)
        .closest("#cost")
        .html("...");
    ss_user_edit_set_cost($(this).parents("form"));
});
