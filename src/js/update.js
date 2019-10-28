require("../stylesheets/update.scss");
require("./partials/global.js");
require("./partials/stocks.js");
require("./partials/loader.js");
require("./partials/infobox.js");

function markAsUpdate() {
    $("body").addClass("updated");
    $("body").html(
        $("<div>", {
            class: "updated",
            html: "Aktualizacja przebiegła pomyślnie.",
        })
    );

    setTimeout(function() {
        window.location.href = window.location.href + "/..";
    }, 4000);
}

$(document).ready(function($) {
    $("#form_update").submit(function(e) {
        e.preventDefault();

        if (loader.blocked) return;

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("/api/update"),
            data: $(this).serialize(),
            complete: function() {
                loader.hide();
            },
            success: function(content) {
                removeFormWarnings();
                $(".warnings").remove();

                if (content === "Shop does not need updating") {
                    return markAsUpdate();
                }

                if (!(jsonObj = json_parse(content))) return;

                // Wyświetlenie błędów w formularzu
                if (jsonObj.return_id == "warnings") {
                    $(".update_info").html(jsonObj.update_info);
                    $(".window")
                        .removeClass("success")
                        .addClass("danger");
                } else if (jsonObj.return_id == "ok") {
                    return markAsUpdate();
                } else if (jsonObj.return_id == "error") {
                    setTimeout(function() {
                        location.reload();
                    }, 4000);
                } else if (!jsonObj.return_id) {
                    infobox.show_info(lang["sth_went_wrong"], false);
                    return;
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: function(error) {
                infobox.show_info("Wystąpił błąd podczas przeprowadzania instalacji.", false);
            },
        });
    });
});
