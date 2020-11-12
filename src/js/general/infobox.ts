import { __ } from "./i18n";

export const infobox = {
    element: $(""),
    hide_task: 0,

    showInfo(message: string, positive: boolean, length: number = 4000): void {
        if (!message) {
            return;
        }

        // Usuwamy poprzedniego boxa
        infobox.element.remove();

        // Usuwamy poprzedni task usuwajacy info
        if (infobox.hide_task) {
            clearTimeout(infobox.hide_task);
            infobox.hide_task = 0;
        }

        infobox.element = $("<div>", {
            html: message,
            class: "infobox notification " + (positive ? "is-success" : "is-danger"),
        }).hide();

        // Dodajemy element do body
        infobox.element.appendTo("body").fadeIn("slow");

        // Dodajemy uchwyt kliknięcia
        infobox.element.click(function () {
            infobox.element.remove();
        });

        // Tworzymy task usuwajacy info po length milisekundach
        infobox.hide_task = setTimeout(function () {
            infobox.remove();
        }, length); // <-- time in milliseconds
    },

    remove() {
        infobox.element.stop().fadeOut("slow", function () {
            $(this).remove();
        });
    },
};

export const handleErrorResponse = () => infobox.showInfo(__("ajax_error"), false);
export const sthWentWrong = () => infobox.showInfo(__("sth_went_wrong"), false);
