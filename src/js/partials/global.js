window.getnset_template = function(element, template, data, onSuccessFunction) {
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl("/api/template/" + template),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id === "no_access") {
                alert(jsonObj.text);
                location.reload();
            }

            element.html(jsonObj.template);
            onSuccessFunction();
        },
        error: function(error) {
            handleErrorResponse();
            location.reload();
        },
    });
};

window.fetch_data = function(action, data, onSuccessFunction) {
    // Sprawdzenie czy data została przesłana
    data = typeof data !== "undefined" ? data : {};
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    // Dodanie informacji do wysyłanej mapy wartości
    data["action"] = action;

    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("jsonhttp_admin.php"),
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

window.rest_request = function(method, path, data, onSuccessFunction) {
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

window.refresh_blocks = function(bricks, onSuccessFunction) {
    loader.show();

    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    var splittedUrl = document.URL.split("?");
    var query = splittedUrl.length > 1 ? splittedUrl.pop() : "";

    $.ajax({
        type: "GET",
        url: buildUrl("/api/bricks/" + bricks) + "?" + query,
        data: {
            pid: typeof currentPage !== "undefined" ? currentPage : undefined,
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            $.each(jsonObj, function(brick_id, brick) {
                $("#" + brick_id).html(brick.content);
                $("#" + brick_id).attr("class", brick.class);
            });

            onSuccessFunction(content);
        },
        error: function(error) {
            handleErrorResponse();
            location.reload();
        },
    });
};

window.changeUrl = function(data) {
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

window.trimSlashes = function(text) {
    return text.replace(/^\/|\/$/g, "");
};

window.buildUrl = function(path, query = {}) {
    var prefix = typeof baseUrl !== "undefined" ? trimSlashes(baseUrl) + "/" : "";
    var queryString = $.param(query);

    var output = prefix + trimSlashes(path);

    if (queryString) {
        output += "?" + queryString;
    }

    return output;
};

window.removeFormWarnings = function() {
    $(".form_warning").remove();
};

window.showWarnings = function(form, warnings) {
    $.each(warnings, function(name, element) {
        var inputElement = form.find('[name="' + name + '"]');
        var appendedElement = Array.isArray(element) ? element.join("<br />") : element;
        inputElement.closest(".field").append(appendedElement);
        inputElement.effect("highlight", 1000);
    });
};

document.addEventListener("click", function(e) {
    // Do not remove class if user clicked dropdown element
    if (!$(e.target).closest(".dropdown").length) {
        $(".dropdown").removeClass("is-active");
    }
});

$(document).delegate(".dropdown", "click", function() {
    $(this).toggleClass("is-active");
});
