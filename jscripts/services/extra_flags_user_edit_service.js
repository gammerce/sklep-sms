//Zmiana typu usługi
$(document).delegate("#my_current_services .row [name=type]", "change", function () {
    var form = $(this).parents('form:first');

    // Usługa innego modułu
    if (form.data('module') != "extra_flags")
        return;

    form.find(".type_nick").hide();
    form.find(".type_ip").hide();
    form.find(".type_sid").hide();
    form.find(".type_password").hide();
    form.find(".type_" + get_type_name($(this).val())).show();

    if ($(this).val() == "1" || $(this).val() == "2")
        form.find(".type_password").show();
});