import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";
import { get_random_string } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

$(document).delegate("#promo_code_button_add", "click", function() {
    showActionBox(window.currentPage, "add");
});

// Generate code
$(document).delegate("#form_promo_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

$(document).delegate(".table-structure .view-action", "click", function() {
    showActionBox(window.currentPage, "view", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

$(document).delegate(".table-structure .delete_row", "click", function() {
    const rowId = $(this).closest("tr");
    const promoCodeId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl(`/api/admin/promo_codes/${promoCodeId}`),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "ok") {
                // Delete row
                rowId.fadeOut("slow");
                rowId.css({ background: "#FFF4BA" });

                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

$(document).delegate("#form_promo_code_add", "submit", function(e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/promo_codes"),
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
                showWarnings($("#form_promo_code_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
