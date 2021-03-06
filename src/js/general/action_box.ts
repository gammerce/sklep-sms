import { loader } from "./loader";

export const action_box = {
    element: $(""),
    box: $(""),
    exit: $(""),
    created: false,

    create() {
        action_box.element = $("<div>", {
            class: "action_box_wrapper",
        }).hide();

        action_box.box = $("<div>", {
            class: "action_box_wrapper2",
        }).hide();
        action_box.element.prepend(action_box.box);

        action_box.element.appendTo("body");

        action_box.created = true;
    },

    show(content) {
        if (!action_box.created) action_box.create();

        action_box.box.html(content);

        action_box.exit = $("<div>", {
            class: "delete is-medium",
        });
        action_box.box.children(".action_box").prepend(action_box.exit);

        // Łapiemy uchwyt od kliknięcia
        action_box.exit.click(function () {
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

        document.addEventListener("keydown", this._onKeyDown);
    },

    hide() {
        action_box.element.stop().fadeOut("slow", function () {
            action_box.created = false;
            $(this).remove();
        });

        document.removeEventListener("keydown", this._onKeyDown);
    },

    _onKeyDown(e) {
        // 27 is Escape key
        if (e.keyCode === 27) {
            action_box.hide();
        }
    },
};
