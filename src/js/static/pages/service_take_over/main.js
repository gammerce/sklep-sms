$(document).delegate("#form_service_take_over [name=service]", "change", function() {
    if ($(this).val() == "") {
        $("#form_service_take_over")
            .find(".extra_data")
            .html("");
        $("#form_service_take_over")
            .find(".take_over")
            .hide();
        return;
    }

    var serviceId = $(this).val();
    rest_request(
        "GET",
        "/api/services/" + serviceId + "/take_over/create_form",
        {},
        function(html) {
            $("#form_service_take_over .extra_data").html(html);
            $("#form_service_take_over .take_over").show();
        }
    );
});

$(document).delegate("#form_service_take_over", "submit", function(e) {
    e.preventDefault();

    if (loader.blocked) return;
    loader.show();

    var serviceId = $(this).find("[name=service]").val();

    $.ajax({
        type: "POST",
        url: buildUrl("/api/services/" + serviceId + "/take_over"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            removeFormWarnings();

            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "warnings") {
                showWarnings($("#form_service_take_over"), jsonObj.warnings);
            } else if (jsonObj.return_id == "ok") {
                // Przejdź do strony user_own_services
                setTimeout(function() {
                    window.location.href = buildUrl("/page/user_own_services");
                }, 2000);
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
                return;
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
});
