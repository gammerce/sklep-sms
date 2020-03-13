import {get_value} from "./stocks";

export const infobox = {
    element: $(""),
    hide_task: 0,

    // Wyświetlanie informacji
    show_info: function show_info(message, positive, length) {
        if (!message) return;

        // Usuwamy poprzedniego boxa
        infobox.element.remove();

        // Usuwamy poprzedni task usuwajacy info
        if (infobox.hide_task) {
            clearTimeout(infobox.hide_task);
            infobox.hide_task = 0;
        }

        // Przerabiamy długość wyświetlania okna
        length = get_value(length, 4000);

        infobox.element = $("<div>", {
            html: message,
            class: "infobox notification " + (positive ? "is-success" : "is-danger"),
        }).hide();

        // Dodajemy element do body
        infobox.element.appendTo("body").fadeIn("slow");

        // Dodajemy uchwyt kliknięcia
        infobox.element.click(function() {
            infobox.element.remove();
        });

        // Tworzymy task usuwajacy info po length milisekundach
        infobox.hide_task = setTimeout(function() {
            infobox.remove();
        }, length); // <-- time in milliseconds
    },

    remove: function() {
        infobox.element.stop().fadeOut("slow", function() {
            $(this).remove();
        });
    },
};

export const handleErrorResponse = function() {
    infobox.show_info(lang["ajax_error"], false);
};

export const sthWentWrong = function() {
    infobox.show_info(lang["sth_went_wrong"], false);
};
