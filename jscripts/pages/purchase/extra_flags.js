/**
 * Sprawdza, czy działa na elemencie stworzonym przez moduł extra_flags
 * Jeżeli tak, to zwraca obiekt najwyżej w drzewie, który został utworzony przez dany moduł
 *
 * @param a
 * @returns {*}
 */
function service_module_act_can(name, a) {
	var element = element_with_data_module(a);
	return element !== null && element.data("module") == name ? element : false;
}

jQuery(document).ready(function ($) {
	var module;
	if (!(module = service_module_act_can("extra_flags", $("#form_purchase"))))
		return;

	// Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
	module.find("#purchase_value").val("0");
});

// Zmiana typu zakupu
$(document).delegate("#form_purchase input[name=type]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	module.find("#type_nick").hide();
	module.find("#type_ip").hide();
	module.find("#type_sid").hide();
	module.find("#type_password").hide();
	module.find("#type_" + get_type_name($(this).val())).show();
	if ($(this).val() == "1" || $(this).val() == "2")
		module.find("#type_password").show();
});

// Zmiana wartości zakupu
$(document).delegate("#form_purchase [name=value]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	if ($(this).val().length)
		module.find("#cost_wraper").slideDown('slow');
	else
		module.find("#cost_wraper").slideUp('slow');

	var values = $(this).val().split(';');
	module.find("#cost_transfer").text(parseFloat(values[0]).toFixed(2));
	if (values[1] != "0") {
		module.find("#cost_sms").text(parseFloat(values[1]).toFixed(2));
		module.find("#currency_sms").show();
	}
	else {
		module.find("#cost_sms").text(lang['none']);
		module.find("#currency_sms").hide();
	}
});

// Zmiana serwera
$(document).delegate("#form_purchase [name=server]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	module.find("#cost_wraper").slideUp();
	if ($(this).val() == "") {
		module.find("[name=value]").children().not("[value='']").remove();
		return;
	}

	fetch_data("execute_service_action", false, {
		service_action: "tariffs_for_server",
		server: $(this).val(),
		service: module.find("[name=service]").val()
	}, function (html) {
		module.find("[name=value]").html(html);
	});
});