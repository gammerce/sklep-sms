export const window_info = {
    element: $(""),
    exit: $(""),

    create(width, height, text) {
        window_info.element = $("<div>", {
            width: width,
            height: height,
            html: text,
            class: "window_info centered",
        }).hide();

        window_info.exit = $("<div>", {
            class: "delete is-large",
        });
        window_info.element.prepend(window_info.exit);

        window_info.element.appendTo("body").slideDown("slow");

        window_info.exit.click(function() {
            window_info.remove();
        });
    },

    remove() {
        window_info.element.slideUp("normal", function() {
            $(this).remove();
        });
    },
};
