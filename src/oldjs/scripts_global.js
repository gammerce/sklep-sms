function getnset_template(element, template, admin, data, onSuccessFunction) {
    // Sprawdzenie czy data została przesłana
    data = typeof data !== "undefined" ? data : {};
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    // Dodanie informacji do wysyłanej mapy wartości
    data["action"] = "get_template";
    data["template"] = template;

    // Wyswietlenie ładowacza
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php"),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id == "no_access") {
                alert(jsonObj.text);
                location.reload();
            }

            element.html(jsonObj.template);
            onSuccessFunction();
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
            location.reload();
        },
    });
}

function fetch_data(action, admin, data, onSuccessFunction) {
    // Sprawdzenie czy data została przesłana
    data = typeof data !== "undefined" ? data : {};
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    // Dodanie informacji do wysyłanej mapy wartości
    data["action"] = action;

    // Wyswietlenie ładowacza
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php"),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            onSuccessFunction(content);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
    });
}

function refresh_blocks(bricks, admin, onSuccessFunction) {
    // Wyswietlenie ładowacza
    loader.show();

    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    var splittedUrl = document.URL.split("?");
    var query = splittedUrl.length > 1 ? splittedUrl.pop() : "";

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php") + "?" + query,
        data: {
            action: "refresh_blocks",
            bricks: bricks,
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
            infobox.show_info(lang["ajax_error"], false);
            location.reload();
        },
    });
}

function changeUrl(data) {
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
}

function trimSlashes(text) {
    return text.replace(/^\/|\/$/g, "");
}

function buildUrl(path) {
    var prefix = typeof baseUrl !== "undefined" ? trimSlashes(baseUrl) + "/" : "";
    return prefix + trimSlashes(path);
}

function removeFormWarnings() {
    $(".form_warning").remove();
}

function showWarnings(form, warnings) {
    $.each(warnings, function(name, element) {
        var inputElement = form.find('[name="' + name + '"]');
        inputElement.closest(".field").append(element);
        inputElement.effect("highlight", 1000);
    });
}
