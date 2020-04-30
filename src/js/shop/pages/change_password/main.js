import { getAndSetTemplate } from "../../utils/utils";
import { json_parse } from "../../../general/stocks";
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

            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_change_password"), jsonObj.warnings);
            } else if (jsonObj.return_id === "password_changed") {
                getAndSetTemplate($("#page-content"), "reset_password_changed");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
