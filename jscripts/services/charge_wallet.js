jQuery(document).ready(function ($) {
    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    $("#form_purchase [name=method]").prop('checked', false);
    $("#charge_table tbody").hide();
    $("#charge_table tfoot").hide();
});

// Zmiana sposobu doładowania
$(document).delegate("#form_purchase [name=method]", "change", function () {
    $("#charge_table tbody").hide();
    if ($(this).val() == "sms") {
        $("#charge_sms").show();
    }
    else if ($(this).val() == "transfer") {
        $("#charge_transfer").show();
    }
    $("#charge_table tfoot").show();
});