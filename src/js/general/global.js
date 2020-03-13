import {loader} from "./loader";
import {handleErrorResponse} from "./infobox";
import {get_value} from "./stocks";

export const restRequest = function(method, path, data, onSuccessFunction) {
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    loader.show();

    $.ajax({
        type: method,
        url: buildUrl(path),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            onSuccessFunction(content);
        },
        error: handleErrorResponse,
    });
};

export const changeUrl = function(data) {
    data = get_value(data, {});
    var splittedUrl = document.URL.split("?");
    var url = splittedUrl[0];
    var query = splittedUrl.length > 1 ? splittedUrl.pop() : "";

    var params = {};

    if (query) {
        $.each(query.split("&"), function(key, value) {
            var param = value.split("=");

            if (param[1].length) params[param[0]] = param[1];
            else delete params[param[0]];
        });
    }

    $.each(data, function(key, value) {
        if (value.length) params[key] = encodeURIComponent(value);
        else delete params[key];
    });

    var strparams = [];
    $.each(params, function(key, value) {
        strparams.push(encodeURIComponent(key) + "=" + encodeURIComponent(value));
    });

    window.location.href = url + "?" + strparams.join("&");
};

export const trimSlashes = function(text) {
    return text.replace(/^\/|\/$/g, "");
};

export const buildUrl = function(path, query) {
    var prefix = typeof baseUrl !== "undefined" ? trimSlashes(baseUrl) + "/" : "";
    var queryString = $.param(query || {});

    var output = prefix + trimSlashes(path);

    if (queryString) {
        output += "?" + queryString;
    }

    return output;
};

export const removeFormWarnings = function() {
    $(".form_warning").remove();
};

export const showWarnings = function(form, warnings) {
    $.each(warnings, function(name, element) {
        var inputElement = form.find('[name="' + name + '"]');
        var appendedElement = Array.isArray(element) ? element.join("<br />") : element;
        inputElement.closest(".field").append(appendedElement);
        inputElement.effect("highlight", 1000);
    });
};

export const hideAndDisable = function(node) {
    node.hide();
    node.find("input").prop("disabled", true);
};

export const showAndEnable = function(node) {
    node.show();
    node.find("input").prop("disabled", false);
};
