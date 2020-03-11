$(document).delegate("#sms_code_button_add", "click", function() {
    show_action_box(currentPage, "sms_code_add");
});

$(document).delegate("#form_sms_code_add [name=random_code]", "click", function() {
    $(this)
        .closest("form")
        .find("[name=code]")
        .val(get_random_string());
});

$(document).delegate("#form_sms_code_add [name=forever]", "change", function() {
    const form = $(this).closest("form");
    form.find("[name=expire]").prop("disabled", $(this).prop("checked"));
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

                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});

// Dodanie kodu SMS
$(document).delegate("#form_sms_code_add", "submit", function(e) {
    e.preventDefault();
    loader.show();

    var formData = $(this).serializeArray();
    console.log(formData);

    $.ajax({
        type: "POST",
        url: buildUrl("/api/admin/sms_codes"),
        data: {
            code: formData.code,
            sms_price: formData.sms_price,
            expires: formData.forever ? null : formData.expires,
        },
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
                showWarnings($("#form_sms_code_add"), jsonObj.warnings);
            } else if (jsonObj.return_id === "ok") {
                clearAndHideActionBox();
                refresh_blocks("admincontent");
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
