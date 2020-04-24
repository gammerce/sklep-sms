import { loader } from "../../../general/loader";
import { json_parse } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { refreshAdminContent } from "../../utils/utils";
import { buildUrl } from "../../../general/global";

$(document).delegate(".table-structure .delete_row", "click", function() {
    const rowId = $(this).closest("tr");
    const logId = rowId.children("td[headers=id]").text();

    if (!confirm(`Na pewno chcesz usunąć log: ${logId} ?`)) {
        return;
    }

    loader.show();

    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/logs/" + logId),
        complete() {
            loader.hide();
        },
        success(content) {
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "ok") {
                // Delete row
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });

                refreshAdminContent();
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
