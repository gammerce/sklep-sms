import { clearAndHideActionBox, refreshAdminContent, showActionBox } from "../../utils/utils";
import { loader } from "../../../general/loader";
import {
    buildUrl,
    changeUrl,
    removeFormWarnings,
    restRequest,
    showWarnings,
} from "../../../general/global";
import { handleErrorResponse, infobox, sthWentWrong } from "../../../general/infobox";

// Kliknięcie dodania usługi użytkownika
$(document).delegate("#user_service_button_add", "click", function () {
    showActionBox(window.currentPage, "add");
});

// Kliknięcie edycji usługi użytkownika
$(document).delegate("[id^=edit_row_]", "click", function () {
    var rowId = $("#" + $(this).attr("id").replace("edit_row_", "row_"));
    showActionBox(window.currentPage, "edit", {
        id: rowId.children("td[headers=id]").text(),
    });
});
$(document).delegate(".table-structure .edit_row", "click", function () {
    showActionBox(window.currentPage, "edit", {
        id: $(this).closest("tr").find("td[headers=id]").text(),
    });
});

// Wybranie modułu
$(document).delegate("#user_service_display_module", "change", function () {
    changeUrl({
        subpage: $(this).val(),
    });
});

// Ustawienie na zawsze
$(document).delegate("#form_user_service_add [name=forever]", "change", function () {
    if ($(this).prop("checked")) $("#form_user_service_add [name=amount]").prop("disabled", true);
    else $("#form_user_service_add [name=amount]").prop("disabled", false);
});

// Wybranie usługi podczas dodawania usługi użytkownikowi
var userServiceAddForm;
$(document).delegate("#form_user_service_add [name=service_id]", "change", function () {
    var serviceId = $(this).val();

    // Brak wybranego modułu
    if (!serviceId && userServiceAddForm) {
        userServiceAddForm.remove();
        return;
    }

    restRequest(
        "GET",
        "/api/admin/services/" + serviceId + "/user_services/add_form",
        {},
        function (content) {
            if (userServiceAddForm) {
                userServiceAddForm.remove();
            }

            userServiceAddForm = $(content);
            userServiceAddForm.insertAfter(".action_box .ftbody");
        }
    );
});

// Usuwanie usługi użytkownika
$(document).delegate("[id^=delete_row_]", "click", function () {
    var rowId = $("#" + $(this).attr("id").replace("delete_row_", "row_"));

    var userServiceId = rowId.children("td[headers=id]").text();
    var confirmText = "Na pewno chcesz usunąć usluge o ID: " + userServiceId + " ?";
    if (confirm(confirmText) == false) {
        return;
    }

    loader.show();
    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/user_services/" + userServiceId),
        complete() {
            loader.hide();
        },
        success(content) {
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

// Delete user service
$(document).delegate(".table-structure .delete_row", "click", function () {
    var rowId = $(this).closest("tr");
    var userServiceId = rowId.children("td[headers=id]").text();

    var confirmText = "Na pewno chcesz usunąć usluge o ID: " + userServiceId + " ?";
    if (confirm(confirmText) == false) {
        return;
    }

    loader.show();

    $.ajax({
        type: "DELETE",
        url: buildUrl("/api/admin/user_services/" + userServiceId),
        complete() {
            loader.hide();
        },
        success(content) {
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

// Add user service
$(document).delegate("#form_user_service_add", "submit", function (e) {
    e.preventDefault();

    var serviceId = $(this).find("[name=service_id]").val();

    loader.show();
    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/services/" + serviceId + "/user_services"),
        data: $(this).serialize(),
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_user_service_add"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Edit user service
$(document).delegate("#form_user_service_edit", "submit", function (e) {
    e.preventDefault();

    var userServiceId = $(this).find("[name=id]").val();

    loader.show();
    $.ajax({
        type: "PUT",
        url: buildUrl("/api/admin/user_services/" + userServiceId),
        data: $(this).serialize(),
        complete() {
            loader.hide();
        },
        success(content) {
            removeFormWarnings();

            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "warnings") {
                showWarnings($("#form_user_service_edit"), content.warnings);
            } else if (content.return_id === "ok") {
                clearAndHideActionBox();
                refreshAdminContent();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});
