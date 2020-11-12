import "../../stylesheets/setup/install.scss";

import "core-js";
import { infobox, sthWentWrong } from "../general/infobox";
import { loader } from "../general/loader";
import { buildUrl, removeFormWarnings } from "../general/global";
import { Dict } from "../shop/types/general";

jQuery(document).ready(function ($) {
    $("#form_install").submit(function (e) {
        e.preventDefault();

        if (loader.blocked) {
            return;
        }

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("/api/install"),
            data: $(this).serialize(),
            complete() {
                loader.hide();
            },
            success(content: Dict) {
                removeFormWarnings();
                $(".warnings").remove();

                if (!content.return_id) {
                    return sthWentWrong();
                }

                // Wyświetlenie błędów w formularzu
                if (content.return_id === "warnings") {
                    for (const [name, element] of Object.entries<any>(content.warnings)) {
                        if (name === "general") {
                            $("<div>", {
                                class: "warnings",
                                html: Array.isArray(element) ? element.join("<br>") : element,
                            }).insertBefore("#form_install");
                            return true;
                        }

                        var fieldElement = $('#form_install [name="' + name + '"]');
                        fieldElement.closest(".field").append(element);
                    }
                } else if (content.return_id === "ok") {
                    $("body").addClass("installed");
                    $("body").html(
                        $("<div>", {
                            class: "installed",
                            html: "Instalacja przebiegła pomyślnie.",
                        }) as any
                    );

                    setTimeout(function () {
                        window.location.href = window.location.href + "/..";
                    }, 4000);

                    return;
                } else if (content.return_id === "error") {
                    setTimeout(function () {
                        location.reload();
                    }, 4000);
                }

                infobox.showInfo(content.text, content.positive);
            },
            error: function (error) {
                infobox.showInfo("Wystąpił błąd podczas przeprowadzania instalacji.", false);
            },
        });
    });
});
