import { hideAndDisable, show, showAndEnable } from "../../../general/global";

$(document).ready(function ($) {
    // Unselect method in case of clicking "back" in the browser
    $("#form_purchase").find("[name=payment_option]").prop("checked", false);
});

$(document).delegate("#form_purchase [name=payment_option]", "change", function () {
    const form = $(this).closest("form");
    const option = $(this).val();

    hideAndDisable(form.find("[data-option]"));
    showAndEnable(form.find(`[data-option="${option}"]`));
    show(form.find(".form-footer"));
});
