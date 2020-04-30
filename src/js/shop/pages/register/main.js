import { getAndSetTemplate } from "../../utils/utils";
import { json_parse } from "../../../general/stocks";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

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

            const jsonObj = json_parse(content);
            if (!jsonObj || !jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "registered") {
                const username = $("#register [name=username]").val();
                const email = $("#register [name=email]").val();

                getAndSetTemplate($("#page-content"), "register_registered", {
                    username: username,
                    email: email,
                });

                window.location.href = buildUrl("/");
            } else {
                if (jsonObj.return_id === "warnings") {
                    showWarnings($("#register"), jsonObj.warnings);
                }

                $("#register [headers=as_question]").html(jsonObj.antispam.question);
                $("#register [name=as_id]").val(jsonObj.antispam.id);
                $("#register [name=as_answer]").val("");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
