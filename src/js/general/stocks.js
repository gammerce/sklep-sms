export const get_value = function(obj, default_value) {
    return typeof obj !== "undefined" ? obj : default_value;
};

export const json_parse = function(text, show) {
    show = typeof show !== "undefined" ? show : true;

    try {
        return JSON.parse(text);
    } catch (err) {
        if (show) alert(text);
        return false;
    }
};

export const get_random_string = function(length) {
    length = get_value(length, 8);
    var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    var final_rand = "";
    for (var i = 0; i < length; i++)
        final_rand += chars[Math.floor(Math.random() * (chars.length - 1))];

    return final_rand;
};

export const element_with_data_module = function(a) {
    if (typeof a.attr("data-module") !== "undefined") return a;

    if (typeof a.prop("tagName") === "undefined") return null;

    return element_with_data_module(a.parent());
};

/**
 * Sprawdza, czy działa na elemencie stworzonym przez moduł extra_flags
 * Jeżeli tak, to zwraca obiekt najwyżej w drzewie, który został utworzony przez dany moduł
 *
 * @param a
 * @returns {*}
 */
export const service_module_act_can = function(name, a) {
    var element = element_with_data_module(a);
    return element !== null && element.data("module") == name ? element : false;
};
