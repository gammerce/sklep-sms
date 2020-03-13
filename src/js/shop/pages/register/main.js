import { getAndSetTemplate } from "../../utils/utils";
import {json_parse} from "../../../general/stocks";
import {loader} from "../../../general/loader";
import {buildUrl, removeFormWarnings, showWarnings} from "../../../general/global";
import {handleErrorResponse, infobox, sthWentWrong} from "../../../general/infobox";

$(document).delegate("#register", "submit", function(e) {
    e.preventDefault();
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("/api/register"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "registered") {
                var username = $("#register [name=username]").val();
                var password = $("#register [name=password]").val();
                var email = $("#register [name=email]").val();
                // Wyświetl informacje o rejestracji
                getAndSetTemplate($("#content"), "register_registered", {
                    username: username,
                    email: email,
                });
                setTimeout(function() {
                    // Logowanie
                    $("#form_login [name=username]").val(username);
                    $("#form_login [name=password]").val(password);
                    $("#form_login_reload_content").val("0");
                    $("#form_login").trigger("submit");
                }, 3000);
            } else {
                if (jsonObj.return_id === "warnings") {
                    showWarnings($("#register"), jsonObj.warnings);
                }

                $("#register .register_antispam [headers=as_question]").html(
                    jsonObj.antispam.question
                );
                $("#register .register_antispam [name=as_id]").val(jsonObj.antispam.id);
                $("#register .register_antispam [name=as_answer]").val("");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
