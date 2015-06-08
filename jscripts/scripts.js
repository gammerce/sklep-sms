jQuery(document).ready(function ($) {
	$(".content_td").append(atob(f));

	/*$("#bck").bind('input',function() {
	 $("body").css({"background-image":"url('"+$(this).val()+"')"});
	 });*/
	$("#language_" + language).addClass("current");

});

function show_info(message, positive, length) {
	infobox.show_info(message, positive, length);
}

function getnset_template(element, template, admin, data, onSuccessFunction) {
	// Sprawdzenie czy data została przesłana
	data = typeof data !== "undefined" ? data : {};
	onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
	};

	// Dodanie informacji do wysyłanej mapy wartości
	data['action'] = "get_template";
	data['template'] = template;

	// Wyswietlenie ładowacza
	loader.show();

	$.ajax({
		type: "POST",
		url: admin ? "jsonhttp_admin.php" : "jsonhttp.php",
		data: data,
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

            if (jsonObj.return_id == "no_access") {
                alert(jsonObj.text);
                location.reload();
            }

			element.html(jsonObj.template);
			onSuccessFunction();
		},
		error: function (error) {
			show_info("Wystąpił błąd przy dynamicznym odświeżaniu strony.", false);
			location.reload();
		}
	});
}

function fetch_data(action, admin, data, onSuccessFunction) {
	// Sprawdzenie czy data została przesłana
	data = typeof data !== "undefined" ? data : {};
	onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
	};

	// Dodanie informacji do wysyłanej mapy wartości
	data['action'] = action;

	// Wyswietlenie ładowacza
	loader.show();

	$.ajax({
		type: "POST",
		url: admin ? "jsonhttp_admin.php" : "jsonhttp.php",
		data: data,
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			onSuccessFunction(content);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy dynamicznym odświeżaniu strony.", false);
		}
	});
}

function refresh_bricks(bricks, admin, onSuccessFunction) {
	// Wyswietlenie ładowacza
	loader.show();

	onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
	};

	$.ajax({
		type: "POST",
		url: (admin ? "jsonhttp_admin.php" : "jsonhttp.php") + "?" + document.URL.split("?").pop(),
		data: {
			action: 'refresh_bricks',
			bricks: bricks
		},
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

			$.each(jsonObj, function (brick_id, brick) {
				$("#" + brick_id).html(brick.content);
				$("#" + brick_id).attr('class', brick.class);
			});

			onSuccessFunction(content);
		},
		error: function (error) {
			show_info("Wystąpił błąd przy dynamicznym odświeżaniu strony.", false);
			location.reload();
		}
	});
}

/**
 * Funkcja przechodzi do strony płatności
 *
 * @param url
 * @param data
 * @param sign
 */
function go_to_payment(data, sign) {
	var form = $('<form>', {
		action: "index.php?pid=payment",
		method: "POST"
	});

	// Dodajemy dane
	form.append($('<input>', {
		type: "hidden",
		name: "data",
		value: data
	}));

	// Dodajemy sign danych
	form.append($('<input>', {
		type: "hidden",
		name: "sign",
		value: sign
	}));

	// Bez tego nie dziala pod firefoxem
	$('body').append(form);

	// Wysyłamy formularz zakupu
	form.submit();
}

// Logowanie
$(document).delegate("#form_login", "submit", function (e) {
	e.preventDefault();
	loader.show();

	$.ajax({
		type: "POST",
		url: "jsonhttp.php",
		data: $(this).serialize() + "&action=login",
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

			// Wyświetlenie błędów w formularzu
			if (jsonObj.return_id == "logged_in") {
				$("#user_buttons").css({"overflow": "hidden"}); // Znikniecie pola do logowania
				refresh_bricks("logged_info;wallet;user_buttons" + ($("#form_login_reload_content").val() == "0" ? "" : ";content"));
			}
			if (jsonObj.return_id == "already_logged_in") {
				location.reload();
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd podczas próby zalogowania.", false);
		}
	});
});

// Wylogowywanie
$(document).delegate("#logout", "click", function (e) {
	// Wyswietlenie ładowacza
	loader.show();

	$.ajax({
		type: "POST",
		url: "jsonhttp.php",
		data: {
			action: 'logout'
		},
		complete: function () {
			loader.hide();
		},
		success: function (content) {
			if (!(jsonObj = json_parse(content)))
				return;

			// Wyświetlenie błędów w formularzu
			if (jsonObj.return_id == "logged_out") {
				//$("#user_buttons").css({"overflow": "hidden"}); // Znikniecie pola do logowania
				refresh_bricks("logged_info;wallet;user_buttons;content");
			}
			if (jsonObj.return_id == "already_logged_out") {
				location.reload();
			}
			else if (!jsonObj.return_id) {
				show_info(lang['sth_went_wrong'], false);
			}

			// Wyświetlenie zwróconego info
			show_info(jsonObj.text, jsonObj.positive);
		},
		error: function (error) {
			show_info("Wystąpił błąd podczas próby wylogowania.", false);
		}
	});
});

// Rozwiniecie pola do logowania
$(document).delegate("#loginarea_roll_button", "click", function () {
	var area = $(".loginarea");
	$(".loginarea table").stop().animate({
		marginLeft: '0px'
	}, 500, function () {
		$("#user_buttons").css({"overflow": area.css("overflow") != "hidden" ? "hidden" : "visible"});
		$(".loginarea table").stop().animate({
			marginLeft: '-220px'
		}, 500);
	});
});

// Wybranie języka
$(document).delegate("#language_choice img", "click", function () {
	var lang_clicked = $(this).attr("id").replace('language_', '');

	fetch_data("set_session_language", false, {language: lang_clicked}, function () {
		location.reload();
	});
});