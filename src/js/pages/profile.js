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

            var jsonObj = json_parse(content);

            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "warnings") {
                showWarnings($(that), jsonObj.warnings);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: handleErrorResponse,
    });
});
