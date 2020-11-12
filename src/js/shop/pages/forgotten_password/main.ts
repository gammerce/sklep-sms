import { getAndSetTemplate } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#form_forgotten_password", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/password/forgotten"),
        data: $(this).serialize(),
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_forgotten_password"), content.warnings);
            } else if (content.return_id === "sent") {
                getAndSetTemplate($("#page-content"), "forgotten_password_sent");
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
