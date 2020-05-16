import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox } from "../../../general/infobox";
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
                content = JSON.parse(content);

                const box = $(button)
                    .closest(".row")
                    .find(".regenerated-token-box");

                show(box);
                box.find(".token-value").text(content.token);

                infobox.showInfo("Token został zregenerowany", true);
            },
            error: function() {
                handleErrorResponse();
                location.reload();
            },
        });
    }
}

function ss_user_edit_set_cost(form) {
    const tmpData = form.serializeArray();
    const data = {};
    $.each(tmpData, function(index, element) {
        data[element.name] = element.value;
    });
    data["user_service_id"] = form.data("row");

    const serviceId = form.find("[name=service_id]").val();

    restRequest("POST", `/api/services/${serviceId}/actions/get_cost_user_edit`, data, function(
        content
    ) {
        const cost = form.find("#cost .price");
        const costMonthly = form.find("#cost-monthly");

        content = JSON.parse(content);
        cost.html(content.surcharge);
        costMonthly.html(content.cost_monthly);
    });
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
