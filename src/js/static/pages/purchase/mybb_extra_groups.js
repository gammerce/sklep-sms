// Zmiana wartości zakupu
$(document).delegate("#form_purchase [name=amount]", "change", function() {
    var form = $(this).closest("form");

    if ($(this).val().length) form.find("#cost_wraper").slideDown("slow");
    else {
        form.find("#cost_wraper").slideUp("slow");
        return;
    }

    // TODO Change it
    var values = $(this)
        .val()
        .split(";");
    form.find("#cost_transfer").text(parseFloat(values[0]).toFixed(2));

    if (values[1] != "0") {
        form.find("#cost_sms").text(parseFloat(values[1]).toFixed(2));
        form.find("#currency_sms").show();
    } else {
        form.find("#cost_sms").text(lang["none"]);
        form.find("#currency_sms").hide();
    }
});
