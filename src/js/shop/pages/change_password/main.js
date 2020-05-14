import { getAndSetTemplate } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#form_change_password", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/password"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_change_password"), content.warnings);
            } else if (content.return_id === "password_changed") {
                getAndSetTemplate($("#page-content"), "reset_password_changed");
            }

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
