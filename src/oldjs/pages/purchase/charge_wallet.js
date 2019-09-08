jQuery(document).ready(function($) {
    var form = $("#form_purchase");

    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    form.find("[name=method]").prop("checked", false);
    form.find("#charge_table tbody").hide();
    form.find("#charge_table tfoot").hide();
});

// Zmiana sposobu doładowania
$(document).delegate("#form_purchase [name=method]", "change", function() {
    var form = $(this).closest("form");

    form.find("#charge_table tbody").hide();
    if ($(this).val() == "sms") {
        form.find("#charge_sms").show();
    } else if ($(this).val() == "transfer") {
        form.find("#charge_transfer").show();
    }
    form.find("#charge_table tfoot").show();
});
