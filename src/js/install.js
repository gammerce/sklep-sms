import "core-js";
require("../stylesheets/install.scss");
require("./partials/global.js");
require("./partials/stocks.js");
require("./partials/loader.js");
require("./partials/infobox.js");

jQuery(document).ready(function($) {
    $("#form_install").submit(function(e) {
        e.preventDefault();

        if (loader.blocked) return;

        loader.show();
        $.ajax({
            type: "POST",
            url: buildUrl("/api/install"),
            data: $(this).serialize(),
            complete: function() {
                loader.hide();
            },
            success: function(content) {
                removeFormWarnings();
                $(".warnings").remove();

                var jsonObj = json_parse(content);
                if (!jsonObj) {
                    return;
                }

                if (!jsonObj.return_id) {
                    return sthWentWrong();
                }

                // Wyświetlenie błędów w formularzu
                if (jsonObj.return_id === "warnings") {
                    $.each(jsonObj.warnings, function(name, element) {
                        if (name === "general") {
                            $("<div>", {
                                class: "warnings",
                                html: Array.isArray(element) ? element.join("<br>") : element,
                            }).insertBefore("#form_install");
                            return true;
                        }

                        var fieldElement = $('#form_install [name="' + name + '"]');
                        fieldElement.closest(".field").append(element);
                    });
                } else if (jsonObj.return_id === "ok") {
                    $("body").addClass("installed");
                    $("body").html(
                        $("<div>", {
                            class: "installed",
                            html: "Instalacja przebiegła pomyślnie.",
                        })
                    );

                    setTimeout(function() {
                        window.location.href = window.location.href + "/..";
                    }, 4000);

                    return;
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
