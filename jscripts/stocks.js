function htmlspecialchars(string, quote_style, charset, double_encode) {
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === "undefined" || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) {
        // Put this first to avoid double-encoding
        string = string.replace(/&/g, "&amp;");
    }
    string = string.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    var OPTS = {
        ENT_NOQUOTES: 0,
        ENT_HTML_QUOTE_SINGLE: 1,
        ENT_HTML_QUOTE_DOUBLE: 2,
        ENT_COMPAT: 2,
        ENT_QUOTES: 3,
        ENT_IGNORE: 4,
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== "number") {
        // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            } else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, "&#039;");
    }
    if (!noquotes) {
        string = string.replace(/"/g, "&quot;");
    }

    return string;
}

function get_value(obj, default_value) {
    return typeof obj !== "undefined" ? obj : default_value;
}

function json_parse(text, show) {
    show = typeof show !== "undefined" ? show : true;

    try {
        return JSON.parse(text);
    } catch (err) {
        if (show) alert(text);
        return false;
    }
}

function get_get_param(key) {
    var prmstr = window.location.search.substr(1);
    if (prmstr == null || prmstr == "") return null;

    var prmarr = prmstr.split("&");
    for (var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        if (tmparr[0] == key) return tmparr[1];
    }

    return null;
}

function get_random_string(length) {
    length = get_value(length, 8);
    var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    var final_rand = "";
    for (var i = 0; i < length; i++)
        final_rand += chars[Math.floor(Math.random() * (chars.length - 1))];

    return final_rand;
}

function element_with_data_module(a) {
    if (typeof a.attr("data-module") !== "undefined") return a;

    if (typeof a.prop("tagName") === "undefined") return null;

    return element_with_data_module(a.parent());
}

/**
 * Sprawdza, czy działa na elemencie stworzonym przez moduł extra_flags
 * Jeżeli tak, to zwraca obiekt najwyżej w drzewie, który został utworzony przez dany moduł
 *
 * @param a
 * @returns {*}
 */
function service_module_act_can(name, a) {
    var element = element_with_data_module(a);
    return element !== null && element.data("module") == name ? element : false;
}
