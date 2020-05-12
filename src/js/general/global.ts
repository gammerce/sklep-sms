import {loader} from "./loader";
import {handleErrorResponse} from "./infobox";
import {trimSlashes} from "./stocks";

export const restRequest = function(method: string, path: string, data: any, onSuccessFunction?: any): void {
    loader.show();

    $.ajax({
        type: method,
        url: buildUrl(path),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: onSuccessFunction,
        error: handleErrorResponse,
    });
};

export const buildUrl = function(path: string, query?: object): string {
    const prefix = typeof window.baseUrl !== "undefined" ? trimSlashes(window.baseUrl) + "/" : "";
    const queryString = $.param(query || {});

    let output = prefix + trimSlashes(path);

    if (queryString) {
        output += "?" + queryString;
    }

    return output;
};

export const changeUrl = function(data: Record<any, any>): void {
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
        if (value.length) {
            params[key] = encodeURIComponent(value);
        }
        else {
            delete params[key];
        }
    });

    var strparams = [];
    $.each(params, function(key, value) {
        strparams.push(encodeURIComponent(key) + "=" + encodeURIComponent(value));
    });

    window.location.href = url + "?" + strparams.join("&");
};

export const hideAndDisable = function(node: JQuery): void {
    hide(node);
    node.prop("disabled", true);
    node.find("input").prop("disabled", true);
};

export const showAndEnable = function(node: JQuery): void {
    show(node);
    node.prop("disabled", false);
    node.find("input").prop("disabled", false);
};

export const hide = function(node: JQuery): void {
    node.addClass("is-hidden");
};

export const show = function(node: JQuery): void {
    node.removeClass("is-hidden");
};

export const isShown = function(node: JQuery): boolean {
    return !node.hasClass("is-hidden");
};

export const showWarnings = function(form: JQuery, warnings: Record<string, any>) {
    $.each(warnings, function(name, element) {
        const inputElement = form.find('[name="' + name + '"]');
        const appendedElement = Array.isArray(element) ? element.join("<br />") : element;
        const field = inputElement.closest(".field");

        inputElement.addClass("is-danger");
        field.append(appendedElement);

        // @ts-ignore
        if (inputElement.effect) {
            // @ts-ignore
            inputElement.effect("highlight", 1000);
        }
    });
};

export const removeFormWarnings = function() {
    $(".form_warning").remove();
};

export const element_with_data_module = function(a: JQuery): JQuery | null {
    if (typeof a.attr("data-module") !== "undefined") {
        return a;
    }

    if (typeof a.prop("tagName") === "undefined") {
        return null;
    }

    return element_with_data_module(a.parent());
};
