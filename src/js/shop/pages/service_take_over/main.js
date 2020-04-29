import { json_parse } from "../../../general/stocks";
import { loader } from "../../../general/loader";
import {
    buildUrl,
    hide,
    hideAndDisable,
    removeFormWarnings,
    restRequest,
    show,
    showAndEnable,
    showWarnings,
} from "../../../general/global";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

$(document).delegate("#form_service_take_over [name=service_id]", "change", function() {
    const form = $("#form_service_take_over");
    const serviceId = $(this).val();

    if (serviceId == "") {
        form.find(".extra_data").html("");
        hideAndDisable(form.find(".form-footer"));
    } else {
        restRequest("GET", `/api/services/${serviceId}/take_over/create_form`, {}, function(html) {
            form.find(".extra_data").html(html);
            showAndEnable(form.find(".form-footer"));
        });
    }
});

$(document).delegate("#form_service_take_over", "submit", function(e) {
    e.preventDefault();

    if (loader.blocked) return;
    loader.show();

    var serviceId = $(this)
        .find("[name=service_id]")
        .val();

    $.ajax({
        type: "POST",
        url: buildUrl("/api/services/" + serviceId + "/take_over"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_service_take_over"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                setTimeout(function() {
                    window.location.href = buildUrl("/page/user_own_services");
                }, 2000);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
