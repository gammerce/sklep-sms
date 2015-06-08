//Kliknięcie na płacenie portfelem
$(document).delegate("#pay_wallet", "click", function () {
	$("#sms_details").slideUp();
	purchase_service("wallet");
});

// Kliknięcie na płacenie przelewem
$(document).delegate("#pay_transfer", "click", function () {
	$("#sms_details").slideUp();
	purchase_service("transfer");
});

// Kliknięcie na płacenie smsem
$(document).delegate("#pay_sms", "click", function () {
	if ($("#sms_details").css("display") == "none")
		$("#sms_details").slideDown('slow');
	else
		purchase_service("sms");
});

function purchase_service(method) {
	if (loader.blocked)
		return;

	loader.show();
	$.ajax({
		type: "POST",
		url: "jsonhttp.php",
		data: {
			action: "validate_payment_form",
			method: method,
			sms_code: $("#sms_code").val(),
			purchase_data: $("#payment [name=purchase_data]").val(),
			purchase_sign: $("#payment [name=purchase_sign]").val()
		},
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			$(".form_warning").remove(); // Usuniecie komunikatow o blednym wypelnieniu formualarza

			if (!(jsonObj = json_parse(content)))
				return;

			// Wyświetlenie błędów w formularzu
			if (jsonObj.return_id == "warnings") {
				$.each(jsonObj.warnings, function (name, text) {
					var id = $("#payment [name=\"" + name + "\"]");
					id.parent("td").append(text);
					id.effect("highlight", 1000);
				});
			}
			else if (jsonObj.return_id == "purchased") {
				// Zmiana zawartosci okienka content na info o zakupie
				fetch_data("get_purchase_info", false, {purchase_id: jsonObj.bsid}, function (message) {
					$("#content").html(message);
				});

				// Odswieżenie stanu portfela
				refresh_blocks("wallet", false, function () {
					$("#wallet").effect("highlight", "slow");
				});
			}
			else if (jsonObj.return_id == "transfer") {
				var form = $('<form>', {
					action: jsonObj.data.url,
					method: "POST"
				});

				$.each(jsonObj.data, function (key, value) {
					if (key == "url")
						return true; // continue

					form.append($('<input>', {
						type: "hidden",
						name: key,
						value: value
					}));
				});

				// Bez tego nie dziala pod firefoxem
				$('body').append(form);

				// Wysyłamy formularz zakupu
				form.submit();
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
				return;
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			infobox.show_info("Wystąpił błąd podczas przeprowadzania płatności za zakupy.", false);
		}
	});
}