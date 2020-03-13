import "../../stylesheets/update.scss";

import "core-js";
import { json_parse } from "../general/stocks";
import { loader } from "../general/loader";
import { buildUrl, removeFormWarnings } from "../general/global";
import { infobox, sthWentWrong } from "../general/infobox";

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

                var jsonObj = json_parse(content);
                if (!jsonObj) {
                    return;
                }

                if (!jsonObj.return_id) {
                    return sthWentWrong();
                }

                // Wyświetlenie błędów w formularzu
                if (jsonObj.return_id === "warnings") {
                    $(".update_info").html(jsonObj.update_info);
                    $(".window")
                        .removeClass("success")
                        .addClass("danger");
                } else if (jsonObj.return_id === "ok") {
                    return markAsUpdate();
                } else if (jsonObj.return_id === "error") {
                    setTimeout(function() {
                        location.reload();
                    }, 4000);
                }

                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: function(error) {
                infobox.show_info("Wystąpił błąd podczas przeprowadzania instalacji.", false);
            },
        });
    });
});
