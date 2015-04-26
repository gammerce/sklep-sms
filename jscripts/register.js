// Wysłanie formularza rejestracyjnego
$(document).delegate("#register", "submit", function (e) {
    e.preventDefault();
    loader.show();

    $.ajax({
        type: "POST",
        url: "jsonhttp.php",
        data: $(this).serialize() + "&action=register",
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            $(".form_warning").remove(); // Usuniecie komuniaktow o blednym wypelnieniu formualarza

            if (!(jsonObj = json_parse(content)))
                return;

            $("#register .register_antispam [headers=as_question]").html(jsonObj.antispam.question);
            $("#register .register_antispam [name=as_id]").val(jsonObj.antispam.id);
            $("#register .register_antispam [name=as_answer]").val("");
            $("#register [name=sign]").val(jsonObj.antispam.sign);

            // Wyświetlenie błędów w formularzu
            if (jsonObj.return_id == "warnings") {
                $.each(jsonObj.warnings, function (name, text) {
                    var id = $("#register [name=\"" + name + "\"]");
                    id.parent("td").append(text);
                    id.effect("highlight", 1000);
                });
            }
            else if (jsonObj.return_id == "registered") {
                var username = $("#register [name=username]").val();
                var password = $("#register [name=password]").val();
                var email = $("#register [name=email]").val();
                // Wyświetl informacje o rejestracji
                getnset_template($("#content"), "register_registered", false, {username: username, email: email});
                setTimeout(function () {
                    // Logowanie
                    $("#form_login [name=username]").val(username);
                    $("#form_login [name=password]").val(password);
                    $("#form_login_reload_content").val("0");
                    $("#form_login").trigger("submit");
                }, 3000);
            }
            else if (!jsonObj.return_id) {
                show_info(lang['sth_went_wrong'], false);
            }

            // Wyświetlenie zwróconego info
            show_info(jsonObj.text, jsonObj.positive);
        },
        error: function (error) {
            show_info("Wystąpił błąd podczas wysyłania formualarza rejestracyjnego.", false);
        }
    });
});