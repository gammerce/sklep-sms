$(document).delegate("#form_take_over_service [name=type]", "change", function () {
    var data_brick = $(this).parents(".table_form");
    if (data_brick.data("module") != "extra_flags")
        return;

    data_brick.find("[data-type='nick']").hide();
    data_brick.find("[data-type='ip']").hide();
    data_brick.find("[data-type='sid']").hide();
    data_brick.find("[data-type='nick']").hide();
    data_brick.find("[data-type='password']").hide();
    data_brick.find("[data-type='" + get_type_name($(this).val()) + "']").show();
    if ($(this).val() == "1" || $(this).val() == "2")
        data_brick.find("[data-type='password']").show();
});

$(document).delegate("#form_take_over_service [name=payment]", "change", function () {
    var data_brick = $(this).parents(".table_form");
    if (data_brick.data("module") != "extra_flags")
        return;

    data_brick.find("[data-name='payment_id']").hide();
    if ($(this).val() == "sms" || $(this).val() == "transfer")
        data_brick.find("[data-name='payment_id']").show();
});