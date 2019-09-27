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

// Wyszukiwanie usługi
$(document).delegate(".table_structure .search", "submit", function(e) {
    e.preventDefault();

    changeUrl({
        search: $(this)
            .find(".search_text")
            .val(),
        page: "",
    });
});

/**
 * Tworzy okienko akcji danej strony
 *
 * @param {string} pageId
 * @param {string} boxId
 * @param {object} data
 */
function show_action_box(pageId, boxId, data) {
    data = typeof data !== "undefined" ? data : {};

    data["page_id"] = pageId;
    data["box_id"] = boxId;
    fetch_data("get_action_box", true, data, function(content) {
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
}
