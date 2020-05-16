import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { get_random_string } from "../../../general/stocks";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#sms_code_button_add", "click", function() {
    showActionBox(window.currentPage, "add");
});

$(document).delegate("#form_sms_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

// Delete sms code
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var smsCodeId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/sms_codes/" + smsCodeId),
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

// Dodanie kodu SMS
$(document).delegate("#form_sms_code_add", "submit", function(e) {
    e.preventDefault();
    loader.show();

    const formData = Object.fromEntries(
        $(this)
            .serializeArray()
            .map(item => [item.name, item.value])
    );

    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/sms_codes"),
        data: {
            code: formData.code,
            sms_price: formData.sms_price,
            expires_at: formData.forever ? null : formData.expires_at,
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_sms_code_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
