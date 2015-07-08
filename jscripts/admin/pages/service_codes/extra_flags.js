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

// Zmiana serwera
$(document).delegate("#form_add_service_code [name=server]", "change", function () {
	var module;
	if (!(module = service_module_act_can("extra_flags", $(this))))
		return;

	if (!$(this).val().length) {
		module.find("[name=amount]").children().not("[value='']").remove();
		return;
	}

	fetch_data("execute_service_action", false, {
		service_action: "tariffs_for_server",
		server: $(this).val(),
		service: module.find("[name=service]").val()
	}, function (html) {
		module.find("[name=amount]").html(html);
	});
});