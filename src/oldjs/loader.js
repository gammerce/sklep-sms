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
                class: "loader_wrapper",
            }).hide();

            loader.element.prepend(
                $("<div>", {
                    class: "loader",
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
