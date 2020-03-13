import "core-js";
require("../stylesheets/admin.scss");
require("./partials/global.js");
require("./partials/stocks.js");
require("./partials/window.js");
require("./partials/loader.js");
require("./partials/infobox.js");

jQuery.fn.scrollTo = function(elem, speed) {
    $(this).animate(
        {
            scrollTop: $(this).scrollTop() - $(this).offset().top + $(elem).offset().top,
        },
        speed == undefined ? 1000 : speed
    );
    return this;
};

window.getAndSetTemplate = function(element, template, data, onSuccessFunction) {
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl("/api/admin/templates/" + template),
        data: data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

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

window.refresh_blocks = function(bricks, onSuccessFunction) {
    loader.show();

    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    var splittedUrl = document.URL.split("?");
    var query = splittedUrl.length > 1 ? splittedUrl.pop() : "";

    $.ajax({
        type: "GET",
        url: buildUrl("/api/admin/bricks/" + bricks) + "?" + query,
        data: {
            pid: typeof currentPage !== "undefined" ? currentPage : undefined,
        },
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

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

/**
 * Tworzy okienko akcji danej strony
 *
 * @param {string} pageId
 * @param {string} boxId
 * @param {object} data
 */
window.show_action_box = function(pageId, boxId, data) {
    restRequest("GET", "/api/admin/pages/" + pageId + "/action_boxes/" + boxId, data, function(
        content
    ) {
        var jsonObj = json_parse(content);
        if (!jsonObj) {
            return;
        }

        // Nie udalo sie prawidlowo pozyskac danych
        if (jsonObj.return_id !== "ok") {
            alert(jsonObj.text);
            location.reload();
        }

        action_box.show(jsonObj.template);
    });
};

window.clearAndHideActionBox = function() {
    action_box.hide();
    $("#action_box_wrapper_td").html("");
};

// Wyszukiwanie usługi
$(document).delegate(".table-structure .search", "submit", function(e) {
    e.preventDefault();

    changeUrl({
        search: $(this)
            .find(".search_text")
            .val(),
        page: "",
    });
});
