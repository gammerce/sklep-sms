import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";
import { refreshAdminContent } from "../../utils/utils";

$(document).delegate("#form_settings_edit", "submit", function (e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/settings"),
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
                showWarnings($("#form_settings_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
