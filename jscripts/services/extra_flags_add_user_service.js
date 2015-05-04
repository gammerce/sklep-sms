// Zmiana typu usługi
$(document).delegate("#form_add_user_service [name=type]", "change", function () {
	$("#type_nick").hide();
	$("#type_ip").hide();
	$("#type_sid").hide();
	$("#type_password").hide();
	$("#type_" + get_type_name($(this).val())).show();

	if ($(this).val() == "1" || $(this).val() == "2")
		$("#type_password").show();
});

// Ustawienie na zawsze
$(document).delegate("#form_add_user_service [name=forever]", "change", function () {
	if ($(this).prop('checked'))
		$("#form_add_user_service [name=amount]").prop('disabled', true);
	else
		$("#form_add_user_service [name=amount]").prop('disabled', false);
});

//Na start odpalamy zmiane typu usługi, żeby ładnie poznikały niepotrzebne elementy
$("#form_add_user_service [name=type]").trigger("change");