$(document).ready(function($) {
    var form = $("#form_purchase");

    // Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
    form.find("[name=method]").prop("checked", false);
    form.find("#charge_table tbody").hide();
    form.find("#charge_table tfoot").hide();
});

// Zmiana sposobu doładowania
$(document).delegate("#form_purchase [name=method]", "change", function() {
    var form = $(this).closest("form");
    var type = $(this).val();

    form.find("#charge_table tbody").hide();
    form.find("#charge_table [data-type=" + type + "]").show();
    form.find("#charge_table tfoot").show();
});
