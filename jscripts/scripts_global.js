function getnset_template(element, template, admin, data, onSuccessFunction) {
    // Sprawdzenie czy data została przesłana
    data = typeof data !== "undefined" ? data : {};
    onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
    };

    // Dodanie informacji do wysyłanej mapy wartości
    data['action'] = "get_template";
    data['template'] = template;

    // Wyswietlenie ładowacza
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php"),
        data: data,
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            if (jsonObj.return_id == "no_access") {
                alert(jsonObj.text);
                location.reload();
            }

            element.html(jsonObj.template);
            onSuccessFunction();
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
            location.reload();
        }
    });
}

function fetch_data(action, admin, data, onSuccessFunction) {
    // Sprawdzenie czy data została przesłana
    data = typeof data !== "undefined" ? data : {};
    onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
    };

    // Dodanie informacji do wysyłanej mapy wartości
    data['action'] = action;

    // Wyswietlenie ładowacza
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php"),
        data: data,
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            onSuccessFunction(content);
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
        }
    });
}

function refresh_blocks(bricks, admin, onSuccessFunction) {
    // Wyswietlenie ładowacza
    loader.show();

    onSuccessFunction = typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function () {
    };

    $.ajax({
        type: "POST",
        url: buildUrl(admin ? "jsonhttp_admin.php" : "jsonhttp.php") + "?" + document.URL.split("?").pop(),
        data: {
            action: 'refresh_blocks',
            bricks: bricks
        },
        complete: function () {
            loader.hide();
        },
        success: function (content) {
            if (!(jsonObj = json_parse(content)))
                return;

            $.each(jsonObj, function (brick_id, brick) {
                $("#" + brick_id).html(brick.content);
                $("#" + brick_id).attr('class', brick.class);
            });

            onSuccessFunction(content);
        },
        error: function (error) {
            infobox.show_info(lang['ajax_error'], false);
            location.reload();
        }
    });
}

function changeUrl(data) {
    data = get_value(data, {});
    var url = window.location.href.split('?');

    if (url[1].length == 0)
        return;

    var params = {};
    $.each((url[1]).split('&'), function (key, value) {
        var param = value.split('=');

        if (param[1].length)
            params[param[0]] = param[1];
        else
            delete params[param[0]];
    });

    $.each(data, function (key, value) {
        if (value.length)
            params[key] = encodeURIComponent(value);
        else
            delete params[key];
    });

    var strparams = [];
    $.each(params, function (key, value) {
        strparams.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
    });

    window.location.href = url[0] + '?' + strparams.join('&');
}

function trimSlashes(text) {
    return text.replace(/^\/|\/$/g, '');
}

function buildUrl(path) {
    return trimSlashes(baseUrl) + '/' + trimSlashes(path);
}
