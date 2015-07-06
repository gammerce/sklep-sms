// Zmiana serwera
$(document).delegate("#form_add_service_code [name=server]", "change", function () {
	// To nie jest element dodany przez moduł extra_flags
	if ($(this).data("module") != "extra_flags")
		return;

	var form = $(this).closest("form");

	if (!$(this).val().length) {
		form.find("[name=amount]").children().not("[value='']").remove();
		return;
	}

	fetch_data("execute_service_action", false, {
		service_action: "tariffs_for_server",
		server: $(this).val(),
		service: form.find("[name=service]").val()
	}, function (html) {
		form.find("[name=amount]").html(html);
	});
});