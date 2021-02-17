import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox } from "../../../general/infobox";
import { buildUrl, restRequest, show } from "../../../general/global";

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
            complete() {
                loader.hide();
            },
            success(content) {
                content = JSON.parse(content);

                const box = $(button).closest(".row").find(".regenerated-token-box");

                show(box);
                box.find(".token-value").text(content.token);

                infobox.showInfo("Token został zregenerowany", true);
            },
            error() {
                handleErrorResponse();
                location.reload();
            },
        });
    }
}

function set_cost(form) {
    const serviceId = form.find("[name=service_id]").val();
    const serializedData = $(form).serialize();
    const userServiceId = form.data("row");
    const data = `${serializedData}&user_service_id=${userServiceId}`;

    restRequest("POST", `/api/services/${serviceId}/actions/get_cost_user_edit`, data, function (
        content
    ) {
        const cost = form.find("#cost .price");
        const costMonthly = form.find("#cost-monthly");

        content = JSON.parse(content);
        cost.html(content.surcharge);
        costMonthly.html(content.cost_monthly);
    });
}

// Zaznaczamy jakas platformę
$(document).delegate(".shopsms_user_edit .platform", "click", function () {
    set_cost($(this).closest("form"));
});

$(document).delegate(".regenerate-token", "click", function () {
    const identifier = $(this).data("identifier");
    regenerate_token(identifier, this);
});
