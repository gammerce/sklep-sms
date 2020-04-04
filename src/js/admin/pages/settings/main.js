import { loader } from "../../../general/loader";
import { json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";
import { refreshAdminContent } from "../../utils/utils";

$(document).delegate("#form_settings_edit", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/settings"),
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

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_settings_edit"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                refreshAdminContent();
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
