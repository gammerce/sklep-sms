$(document).ready(function() {
    if (typeof f !== "undefined") $(".content_td").append(atob(f));

    /*$("#bck").bind('input',function() {
     $("body").css({"background-image":"url('"+$(this).val()+"')"});
     });*/
    $("#language_" + language).addClass("current");
});

/**
 * Funkcja przechodzi do strony płatności
 *
 * @param url
 * @param data
 * @param sign
 */
function go_to_payment(data, sign) {
    var form = $("<form>", {
        action: buildUrl("/page/payment"),
        method: "POST",
    });

    // Dodajemy dane
    form.append(
        $("<input>", {
            type: "hidden",
            name: "data",
            value: data,
        })
    );

    // Dodajemy sign danych
    form.append(
        $("<input>", {
            type: "hidden",
            name: "sign",
            value: sign,
        })
    );

    // Bez tego nie dziala pod firefoxem
    $("body").append(form);

    // Wysyłamy formularz zakupu
    form.submit();
}

// Logowanie
$(document).delegate("#form_login", "submit", function(e) {
    e.preventDefault();
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp.php"),
        data: $(this).serialize() + "&action=login",
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "logged_in") {
                $("#user_buttons").css({ overflow: "hidden" }); // Znikniecie pola do logowania
                refresh_blocks(
                    "logged_info;wallet;user_buttons;services_buttons" +
                        ($("#form_login_reload_content").val() == "0" ? "" : ";content")
                );
            }
            if (jsonObj.return_id == "already_logged_in") {
                location.reload();
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Wylogowywanie
$(document).delegate("#logout", "click", function(e) {
    // Wyswietlenie ładowacza
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp.php"),
        data: {
            action: "logout",
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "logged_out") {
                //$("#user_buttons").css({"overflow": "hidden"}); // Znikniecie pola do logowania
                refresh_blocks("logged_info;wallet;user_buttons;services_buttons;content");
            }
            if (jsonObj.return_id == "already_logged_out") {
                location.reload();
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});

// Rozwiniecie pola do logowania
$(document).delegate("#loginarea_roll_button", "click", function() {
    var area = $(".loginarea");
    $(".loginarea table")
        .stop()
        .animate(
            {
                marginLeft: "0px",
            },
            500,
            function() {
                $("#user_buttons").css({
                    overflow: area.css("overflow") != "hidden" ? "hidden" : "visible",
                });
                $(".loginarea table")
                    .stop()
                    .animate(
                        {
                            marginLeft: "-220px",
                        },
                        500
                    );
            }
        );
});

// Wybranie języka
$(document).delegate("#language_choice img", "click", function() {
    var lang_clicked = $(this)
        .attr("id")
        .replace("language_", "");

    fetch_data("set_session_language", false, { language: lang_clicked }, function() {
        location.reload();
    });
});
