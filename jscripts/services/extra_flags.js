// TODO: Poprzenosić wszystko do odpowiednich miejsc w zależności od tego kiedy korzystamy z danej funkcji

jQuery(document).ready(function ($) {
	// Aby żadna opcja nie była zaznaczona w przypadku użycia "cofnij"
	$("#form_purchase #purchase_value").val("0");
});

// Zmiana typu zakupu
$(document).delegate("#form_purchase input[name=type]", "change", function () {
	$("#type_nick").hide();
	$("#type_ip").hide();
	$("#type_sid").hide();
	$("#type_password").hide();
	$("#type_" + get_type_name($(this).val())).show();
	if ($(this).val() == "1" || $(this).val() == "2")
		$("#type_password").show();
});

// Zmiana wartości zakupu
$(document).delegate("#form_purchase [name=value]", "change", function () {
	if ($(this).val() != "")
		$("#cost_wraper").slideDown('slow');
	else
		$("#cost_wraper").slideUp('slow');

	var values = $(this).val().split(';');
	$("#cost_transfer").text(parseFloat(values[0]).toFixed(2));
	if (values[1] != "0") {
		$("#cost_sms").text(parseFloat(values[1]).toFixed(2));
		$("#currency_sms").show();
	}
	else {
		$("#cost_sms").text(lang['none']);
		$("#currency_sms").hide();
	}
});

// Zmiana serwera
$(document).delegate("#form_purchase [name=server]", "change", function () {
	$("#cost_wraper").slideUp();
	if ($(this).val() == "") {
		$("#form_purchase [name=value]").children().not("[value='']").remove();
		return;
	}

	fetch_data("execute_service_action", false, {
		service_action: "tariffs_for_server",
		server: $(this).val(),
		service: $("#form_purchase [name=service]").val()
	}, function (html) {
		$("#form_purchase [name=value]").html(html);
	});
});

//
// Edycja usługi gracza przez admina w PA
//

// Zmiana usługi przy edycji
$(document).delegate("#form_edit_user_service [name=service]", "change", function () {
	if ($(this).parents("form:first").find("[name=module]").val() != "extra_flags")
		return;

	if ($(this).val() == "") {
		$("#form_edit_user_service [name=server]").children().not("[value='']").remove();
		return;
	}

	fetch_data("execute_service_action", false, {
		service_action: "servers_for_service",
		service: $(this).val(),
		server: $("#form_edit_user_service [name=server]").val()
	}, function (html) {
		$("#form_edit_user_service [name=server]").html(html);
	});
});

// Ustawienie na zawsze przy edycji
$(document).delegate("#form_edit_user_service [name=forever]", "change", function () {
	if ($(this).prop('checked'))
		$("#form_edit_user_service [name=expire]").prop('disabled', true);
	else
		$("#form_edit_user_service [name=expire]").prop('disabled', false);
});

// Zmiana typu usługi
$(document).delegate("#form_edit_user_service [name=type]", "change", function () {
	if ($(this).parents("form:first").find("[name=module]").val() != "extra_flags")
		return;

	$("#type_nick").hide();
	$("#type_ip").hide();
	$("#type_sid").hide();
	$("#type_password").hide();
	$("#type_" + get_type_name($(this).val())).show();

	if ($(this).val() == "1" || $(this).val() == "2")
		$("#type_password").show();
});

//
// Reszta

function get_type_name(value) {
	if (value == "1")
		return "nick";
	else if (value == "2")
		return "ip";
	else if (value == "4")
		return "sid";
}