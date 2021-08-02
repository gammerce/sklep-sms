export const loader = {
    element: $(""),
    show_task: undefined,
    blocked: false,

    show() {
        loader.blocked = true;
        // Let's remove the previous task showing the loader
        if (loader.show_task) {
            clearTimeout(loader.show_task);
            loader.show_task = undefined;
        }

        loader.show_task = setTimeout(function () {
            loader.element = $("<div>", {
                class: "loader-wrapper",
            }).hide();

            loader.element.prepend(
                $("<div>", {
                    class: "loader",
                })
            );

            loader.element.appendTo("body").fadeIn("slow");
            loader.show_task = undefined;
        }, 300);
    },

    hide() {
        loader.blocked = false;
        if (loader.show_task) {
            clearTimeout(loader.show_task);
            loader.show_task = undefined;
        }
        loader.element.remove();
    },
};
