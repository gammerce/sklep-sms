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

$(document).delegate("#form_take_over_service [name=type]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	module.find("[data-type='nick']").hide();
	module.find("[data-type='ip']").hide();
	module.find("[data-type='sid']").hide();
	module.find("[data-type='nick']").hide();
	module.find("[data-type='password']").hide();
	module.find("[data-type='" + get_type_name($(this).val()) + "']").show();
	if ($(this).val() == "1" || $(this).val() == "2")
		module.find("[data-type='password']").show();
});

$(document).delegate("#form_take_over_service [name=payment]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	module.find("[data-name='payment_id']").hide();
	if ($(this).val() == "sms" || $(this).val() == "transfer")
		module.find("[data-name='payment_id']").show();
});