import {getAndSetTemplate} from "../../utils/utils";
import {json_parse} from "../../../general/stocks";
import {loader} from "../../../general/loader";
import {buildUrl, removeFormWarnings, showWarnings} from "../../../general/global";
import {handleErrorResponse, infobox, sthWentWrong} from "../../../general/infobox";

// Wysłanie formularza o reset hasła
$(document).delegate("#form_reset_password", "submit", function(e) {
    e.preventDefault();
    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/password/reset"),
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
                showWarnings($("#form_reset_password"), jsonObj.warnings);
            } else if (jsonObj.return_id === "password_changed") {
                // Wyświetl informacje o zmianie hasła
                getAndSetTemplate($("#content"), "reset_password_changed");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
