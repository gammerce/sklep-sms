jQuery(document).ready(function($) {
    $("#form_install").submit(function(e) {
        e.preventDefault();

        if (loader.blocked) return;

        loader.show();
        $.ajax({
            type: "POST",
            url: "full.php",
            data: $(this).serialize(),
            complete: function() {
                loader.hide();
            },
            success: function(content) {
                removeFormWarnings();
                $(".warnings").remove();

                if (!(jsonObj = json_parse(content))) return;

                // Wyświetlenie błędów w formularzu
                if (jsonObj.return_id == "warnings") {
                    $.each(jsonObj.warnings, function(name, element) {
                        if (name == "general") {
                            $("<div>", {
                                class: "warnings",
                                html: element.join("<br>"),
                            }).insertBefore("#form_install");
                            return true;
                        }

                        var fieldElement = $('#form_install [name="' + name + '"]:first').closest(
                            ".field"
                        );
                        fieldElement.append(element);
                    });
                } else if (jsonObj.return_id == "ok") {
                    $("body").addClass("installed");
                    $("body").html(
                        $("<div>", {
                            class: "installed",
                            html: "Instalacja przebiegła pomyślnie.",
                        })
                    );

                    setTimeout(function() {
                        location.reload();
                    }, 4000);

                    return;
                } else if (jsonObj.return_id == "error") {
                    setTimeout(function() {
                        location.reload();
                    }, 4000);
                } else if (!jsonObj.return_id) {
                    infobox.show_info(lang["sth_went_wrong"], false);
                    return;
                }

                // Wyświetlenie zwróconego info
                infobox.show_info(jsonObj.text, jsonObj.positive);
            },
            error: function(error) {
                infobox.show_info("Wystąpił błąd podczas przeprowadzania instalacji.", false);
            },
        });
    });
});

var loader = {
    element: $(""),
    show_task: 0,
    blocked: false,

    show: function() {
        loader.blocked = true;
        // Usuwamy poprzedni task pokazujacy ladowanie
        if (loader.show_task) {
            clearTimeout(loader.show_task);
            loader.show_task = 0;
        }

        loader.show_task = setTimeout(function() {
            loader.element = $("<div>", {
                class: "loader",
            }).hide();

            loader.element.prepend(
                $("<img>", {
                    src: "../images/ajax-loader.gif",
                    title: "Instalowanie...",
                    class: "centered",
                })
            );

            loader.element.appendTo("body").fadeIn("slow");
            loader.show_task = 0;
        }, 300);
    },

    hide: function() {
        loader.blocked = false;
        if (loader.show_task) {
            clearTimeout(loader.show_task);
            loader.show_task = 0;
        }
        loader.element.remove();
    },
};
