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

//Zmiana typu usługi
$(document).delegate("#user_own_services .row [name=type]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	module.find(".type_nick").hide();
	module.find(".type_ip").hide();
	module.find(".type_sid").hide();
	module.find(".type_password").hide();
	module.find(".type_" + get_type_name($(this).val())).show();

	if ($(this).val() == "1" || $(this).val() == "2")
		module.find(".type_password").show();
});