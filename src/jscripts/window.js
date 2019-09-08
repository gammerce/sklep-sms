var window_info = {
    element: $(""),
    exit: $(""),

    create: function(width, height, text) {
        window_info.element = $("<div>", {
            width: width,
            height: height,
            html: text,
            class: "window_info centered",
        }).hide();

        window_info.exit = $("<div>", {
            class: "exit",
            html: "X",
        });
        window_info.element.prepend(window_info.exit);

        window_info.element.appendTo("body").slideDown("slow");

        window_info.exit.click(function() {
            window_info.remove();
        });
    },

    remove: function() {
        window_info.element.slideUp("normal", function() {
            $(this).remove();
        });
    },
};

var action_box = {
    element: $(""),
    box: $(""),
    exit: $(""),
    created: false,

    create: function() {
        action_box.element = $("<div>", {
            class: "action_box_wraper",
        }).hide();

        action_box.box = $("<div>", {
            class: "action_box_wraper2",
        }).hide();
        action_box.element.prepend(action_box.box);

        action_box.element.appendTo("body");

        action_box.created = true;
    },

    show: function(content) {
        if (!action_box.created) action_box.create();

        action_box.box.html(content);

        action_box.exit = $("<div>", {
            class: "exit",
            html: "X",
        });
        action_box.box.children(".action_box").prepend(action_box.exit);

        // Łapiemy uchwyt od kliknięcia
        action_box.exit.click(function() {
            action_box.hide();
        });

        // Jeżeli jest task loadera, to znaczy, że skrypt jeszcze czeka, na wyświetlenie ładowacza
        // dlatego też ze wszystkich wchodzimy łagodnie
        // w przeciwnym wypadku, łagodnie wchodzimy tylko z oknem akcji, a tło pokazujemy od razu
        if (loader.show_task) {
            action_box.box.show();
            action_box.element.fadeIn();
        } else {
            action_box.element.show();
            action_box.box.fadeIn();
        }
    },

    hide: function() {
        action_box.element.stop().fadeOut("slow", function() {
            action_box.created = false;
            $(this).remove();
        });
    },
};
