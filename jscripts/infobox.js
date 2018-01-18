var infobox = {
    element: $(""),
    hide_task: 0,

    // Wyświetlanie informacji
    show_info: function show_info(message, positive, length) {
        if (!message)
            return;

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
            class: "infobox " + (positive == "1" ? "positive" : "negative")
        }).hide();

        // Dodajemy element do body
        infobox.element.appendTo('body').fadeIn("slow");

        // Dodajemy uchwyt kliknięcia
        infobox.element.click(function () {
            infobox.element.remove();
        });

        // Tworzymy task usuwajacy info po length milisekundach
        infobox.hide_task = setTimeout(function () {
            infobox.remove();
        }, length); // <-- time in milliseconds
    },

    remove: function () {
        infobox.element.stop().fadeOut('slow', function () {
            $(this).remove();
        });
    }
};