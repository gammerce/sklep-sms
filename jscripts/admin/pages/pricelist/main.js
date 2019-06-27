// Kliknięcie dodania ceny
$(document).delegate("#price_button_add", "click", function () {
    show_action_box(get_get_param("pid"), "price_add");
});

// Kliknięcie edycji ceny
$(document).delegate(".table_structure .edit_row", "click", function () {
    show_action_box(get_get_param("pid"), "price_edit", {
        id: $(this).closest('tr').find("td[headers=id]").text()
    });
});

// Usuwanie taryfy
$(document).delegate(".table_structure .delete_row", "click", function () {
    var row_id = $(this).closest('tr');

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: {
            action: "delete_price",
            id: row_id.children("td[headers=id]").text()
        },
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == 'ok') {
                // Usuń row
                row_id.fadeOut("slow");
                row_id.css({"background": "#FFF4BA"});

                // Odśwież stronę
                refresh_blocks("admincontent", true);
            }
            else if (!jsonObj.return_id) {
                infobox.show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
        }
    });
});

// Dodanie ceny
$(document).delegate("#form_price_add", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=price_add",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            $(".form_warning").remove(); // Usuniecie komuniktow o blednym wypelnieniu formularza

            if (!(jsonObj = json_parse(content)))
                return;

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = $("#form_price_add [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == 'ok') {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent", true);
            }
            else if (!jsonObj.return_id) {
                infobox.show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
        }
    });
});

// Edycja taryfy
$(document).delegate("#form_price_edit", "submit", function (e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
        data: $(this).serialize() + "&action=price_edit",
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
                    var id = $("#form_price_edit [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == 'ok') {
                // Ukryj i wyczyść action box
                action_box.hide();
                $("#action_box_wraper_td").html("");

                // Odśwież stronę
                refresh_blocks("admincontent", true);
            }
            else if (!jsonObj.return_id) {
                infobox.show_info(lang['sth_went_wrong'], false);
                return;
            }

            // Wyświetlenie zwróconego info
            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
        }
    });
});
