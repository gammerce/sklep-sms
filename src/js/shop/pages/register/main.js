import { getAndSetTemplate, refreshBlocks } from "../../utils/utils";
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

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "registered") {
                const username = $("#register [name=username]").val();
                const email = $("#register [name=email]").val();

                getAndSetTemplate($("#page-content"), "register_registered", {
                    username: username,
                    email: email,
                });

                refreshBlocks("logged_info,wallet,user_buttons,services_buttons");
            } else {
                if (content.return_id === "warnings") {
                    showWarnings($("#register"), content.warnings);
                }

                $("#register [headers=as_question]").html(content.antispam.question);
                $("#register [name=as_id]").val(content.antispam.id);
                $("#register [name=as_answer]").val("");
            }

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
