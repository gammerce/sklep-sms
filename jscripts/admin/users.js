// Klikniecie doladowania portfela
var row_id = 0;
$(document).delegate("[id^=charge_wallet_]", "click", function () {
    row_id = $("#" + $(this).attr("id").replace('charge_wallet_', 'row_'));
    action_box.create();
    getnset_template(action_box.box, "admin_charge_wallet", true, {
        username: row_id.children("td[headers=username]").text(),
        uid: row_id.children("td[headers=uid]").text()
    }, function () {
        action_box.show();
    });
});

// Kliknięcie edycji użytkownika
$(document).delegate("[id^=edit_row_]", "click", function () {
    var row_id = $("#" + $(this).attr("id").replace('edit_row_', 'row_'));
    action_box.create();
    getnset_template(action_box.box, "admin_edit_user", true, {
        uid: row_id.children("td[headers=uid]").text()
    }, function () {
        action_box.show();
    });
});

// Usuwanie użytkownika
$(document).delegate("[id^=delete_row_]", "click", function () {
    var row_id = $("#" + $(this).attr("id").replace('delete_row_', 'row_'));

    // Czy na pewno?
    if (confirm("Czy na pewno chcesz usunąć konto o ID " + row_id.children("td[headers=uid]").text() + "?") == false) {
        return;
    }

    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: {
            action: "delete_user",
            uid: row_id.children("td[headers=uid]").text()
        },
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == "deleted") {
                // Usuń row
                row_id.fadeOut("slow");
                row_id.css({"background": "#FFF4BA"});

                // Odśwież stronę
                refresh_brick("users", true);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy usuwaniu użytkownika.", false);
        }
    });
});

// Doladowanie portfela
$(document).delegate("#form_charge_wallet", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: $(this).serialize() + "&action=charge_wallet",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content)))
                return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = $("#form_charge_wallet [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == "charged") {
                // Zmień stan portfela
                getnset_template(row_id.children("td[headers=wallet]"), "admin_user_wallet", true,
                    {
                        uid: $("#form_charge_wallet input[name=uid]").val()
                    },
                    function () {
                        // Podświetl row
                        row_id.children("td[headers=wallet]").effect("highlight", 1000);
                    });

                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd podczas doładowywaniu portfela użytkownika.", false);
        }
    });
});

// Edycja uzytkownika
$(document).delegate("#form_edit_user", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: "jsonhttp_admin.php",
        data: $(this).serialize() + "&action=edit_user",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content)))
                return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = $("#form_edit_user [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == "edited") {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_brick("users", true);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy edytowaniu użytkownika.", false);
        }
    });
});