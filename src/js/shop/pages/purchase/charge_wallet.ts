import { hideAndDisable, show, showAndEnable } from "../../../general/global";

$(document).ready(function($) {
    const form = $("#form_purchase");
    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    form.find("[name=method]").prop("checked", false);
});

// Zmiana sposobu doładowania
$(document).delegate("#form_purchase [name=method]", "change", function() {
    const form = $(this).closest("form");
    const type = $(this).val();

    hideAndDisable(form.find("[data-type]"));
    showAndEnable(form.find(`[data-type=${type}]`));
    show(form.find(".form-footer"));
});
