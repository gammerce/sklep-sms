import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#form_profile_update", "submit", function(e) {
    e.preventDefault();

    loader.show();
    const that = this;

    $.ajax({
        type: "PUT",
        url: buildUrl("/api/profile"),
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
                showWarnings($(that), content.warnings);
            }

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
