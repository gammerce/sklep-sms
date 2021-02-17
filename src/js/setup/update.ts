import "../../stylesheets/setup/update.scss";

import "core-js";
import { loader } from "../general/loader";
import { infobox, sthWentWrong } from "../general/infobox";
import { buildUrl, removeFormWarnings } from "../general/global";

function markAsUpdate() {
    $("body").addClass("updated");
    $("body").html(
        $("<div>", {
            class: "updated",
            html: "Aktualizacja przebiegła pomyślnie.",
        }) as any
    );

    setTimeout(function () {
        window.location.href = window.location.href + "/..";
    }, 4000);
}

$(document).ready(function ($) {
    $("#form_update").submit(function (e) {
        e.preventDefault();

        if (loader.blocked) return;

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("/api/update"),
            data: $(this).serialize(),
            complete() {
                loader.hide();
            },
            success(content) {
                removeFormWarnings();
                $(".warnings").remove();

                if (content === "Shop does not need updating") {
                    return markAsUpdate();
                }

                if (!content.return_id) {
                    return sthWentWrong();
                }

                // Wyświetlenie błędów w formularzu
                if (content.return_id === "warnings") {
                    $(".update_info").html(content.update_info);
                    $(".window").removeClass("success").addClass("danger");
                } else if (content.return_id === "ok") {
                    return markAsUpdate();
                } else if (content.return_id === "error") {
                    setTimeout(function () {
                        location.reload();
                    }, 4000);
                }

                infobox.showInfo(content.text, content.positive);
            },
            error(error) {
                infobox.showInfo("Wystąpił błąd podczas przeprowadzania instalacji.", false);
            },
        });
    });
});
