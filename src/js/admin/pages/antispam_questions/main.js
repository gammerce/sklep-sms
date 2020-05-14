import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";
import { buildUrl, removeFormWarnings, showWarnings } from "../../../general/global";

$(document).delegate("#antispam_question_button_add", "click", function() {
    showActionBox(currentPage, "antispam_question_add");
});

$(document).delegate(".table-structure .edit_row", "click", function() {
    showActionBox(currentPage, "antispam_question_edit", {
        id: $(this)
            .closest("tr")
            .find("td[headers=id]")
            .text(),
    });
});

// Delete antispam question
$(document).delegate(".table-structure .delete_row", "click", function() {
    var rowId = $(this).closest("tr");
    var antispamQuestionId = rowId.children("td[headers=id]").text();

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl(`/api/admin/antispam_questions/${antispamQuestionId}`),
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

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Add antispam question
$(document).delegate("#form_antispam_question_add", "submit", function(e) {
    e.preventDefault();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/antispam_questions"),
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
                showWarnings($("#form_antispam_question_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit antispam question
$(document).delegate("#form_antispam_question_edit", "submit", function(e) {
    e.preventDefault();

    var antispamQuestionId = $(this)
        .find("[name=id]")
        .val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/antispam_questions/" + antispamQuestionId),
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
                showWarnings($("#form_antispam_question_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.show_info(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
