import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox } from "../../../general/infobox";
import { json_parse } from "../../../general/stocks";
import { buildUrl, restRequest, show } from "../../../general/global";

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
    const confirmed = confirm(
        `Czy na pewno chcesz ponownie wygenerować token dla licencji: ${identifier} ?`
    );

    if (confirmed) {
        loader.show();

        $.ajax({
            type: "POST",
            url: buildUrl("/api/services/ss_license/actions/regenerate_token"),
            data: {
                identifier: identifier,
            },
            complete: function() {
                loader.hide();
            },
            success: function(content) {
                const response = json_parse(content);

                if (!response) {
                    return;
                }

                const box = $(button)
                    .closest(".row")
                    .find(".regenerated-token-box");
                show(box);
                box.find(".token-value").text(response.token);

                infobox.show_info("Token został zregenerowany", true);
            },
            error: function(error) {
                handleErrorResponse();
                location.reload();
            },
        });
    }
}

function ss_user_edit_set_cost(form) {
    var tmpData = form.serializeArray();
    var data = {};
    $.each(tmpData, function(index, element) {
        data[element.name] = element.value;
    });
    data["user_service_id"] = form.data("row");

    var serviceId = form.find("[name=service_id]").val();

    restRequest(
        "POST",
        "/api/services/" + serviceId + "/actions/get_cost_user_edit",
        data,
        function(content) {
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            const cost = form.find("#cost");
            const costMonthly = form.find("#cost-monthly");

            if (cost.html() != jsonObj.surcharge) {
                // podswietlamy i zmieniamy zawartosc, gdy ta sie rzeczywiscie zmienila
                cost.html(jsonObj.surcharge).effect("highlight", 1000);
            }

            if (costMonthly.html() != jsonObj.cost_monthly) {
                costMonthly.html(jsonObj.cost_monthly).effect("highlight", 1000);
            }
        }
    );
}

// Zaznaczamy jakas gre
$(document).delegate(".shopsms_user_edit .engine", "click", function() {
    let toggle_value = false;
    if ($(this).hasClass("amxx")) {
        toggle_value = engine_toggle(
            $(this)
                .parent()
                .find("[name=platform_amxmodx]")
        );
    } else if ($(this).hasClass("sm")) {
        toggle_value = engine_toggle(
            $(this)
                .parent()
                .find("[name=platform_sourcemod]")
        );
    }

    ss_user_edit_set_cost($(this).closest("form"));

    if (toggle_value) {
        $(this).addClass("is-active");
    } else {
        $(this).removeClass("is-active");
    }
});

$(document).delegate(".regenerate-token", "click", function() {
    const identifier = $(this).data("identifier");
    regenerate_token(identifier, this);
});
