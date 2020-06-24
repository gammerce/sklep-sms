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
        complete() {
            loader.hide();
        },
        success(content) {
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
            } else if (content.return_id === "warnings") {
                showWarnings($("#register"), content.warnings);

                if (content.warnings["h-captcha-response"]) {
                    // @ts-ignore
                    hcaptcha.reset();
                }
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
