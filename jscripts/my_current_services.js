// Kliknięcie edycji usługi
$(document).delegate("#my_current_services .edit_row", "click", function () {
    var row_id = $(this).parents('form:first');

    fetch_data("form_edit_user_service", false, {
        id: row_id.data('row')
    }, function (html) {
        // Podmieniamy zawartość
        row_id.html(html);
        row_id.parents(".brick:first").addClass("active");

        // Dodajemy event, aby powróciło do poprzedniego stanu po kliknięciu "Anuluj"
        row_id.find(".cancel").click({row_id: row_id}, function (e) {
            var row_id = e.data.row_id;
            fetch_data("get_user_service_brick", false, {
                id: row_id.data('row')
            }, function (html) {
                // Podmieniamy zawartość
                row_id.html(html);
                row_id.parents(".brick:first").removeClass("active");
            });
        });

        // Na start odpalamy zmiane typu usługi, żeby ładnie poznikały niepotrzebne elementy
        row_id.find("[name=type]").trigger("change");
    });
});

var purchase_data, purchase_sign;

// Wyedytowanie usługi
$(document).delegate("#my_current_services .row", "submit", function (e) {
    e.preventDefault();

    loader.show();
    var temp_this = $(this);
    $.ajax({
        type: "POST",
        url: "jsonhttp.php",
        data: $(this).serialize() + "&action=edit_user_service&id=" + temp_this.data('row'),
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            temp_this.find(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content)))
                return;

            if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
                return;
            }
            // Wyświetlenie błędów w formularzu
            else if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = temp_this.find("[name=\"" + name + "\"]");
                    id.after(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == "edited") {
                // TODO: Przemyśleć dlaczego jest tu komentarz
                /*fetch_data("get_user_service_brick",false,{
                 id: temp_this.data('row')
                 },function(html) {
                 // Podmieniamy zawartość
                 temp_this.html(html);
                 temp_this.parents(".brick:first").removeClass("active");
                 });*/
                refresh_brick("content");
            }
            else if (jsonObj.return_id == "payment") {
                purchase_data = jsonObj.data; // Tak musi byc, bo inaczej nie bedzie dzialac
                purchase_sign = jsonObj.sign;
                // Zmiana zawartosci okienka content na płatność za zakupy
                fetch_data("payment_form", false, {data: purchase_data, sign: purchase_sign}, function (message) {
                    $("#content").html(message);
                });
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd przy edytowaniu usługi.", false);
        }
    });
});