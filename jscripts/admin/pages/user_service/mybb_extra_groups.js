// Ustawienie na zawsze
$(document).delegate("#form_user_service_add [name=forever]", "change", function () {
	var module;
	if (!(module = service_module_act_can("mybb_extra_groups", $(this))))
		return;

	if ($(this).prop('checked'))
		module.find("[name=amount]").prop('disabled', true);
	else
		module.find("[name=amount]").prop('disabled', false);
});