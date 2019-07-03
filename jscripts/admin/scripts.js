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
 * @param string page_id
 * @param string box_id
 */
function show_action_box(page_id, box_id, data) {
    data = typeof data !== "undefined" ? data : {};

    data["page_id"] = page_id;
    data["box_id"] = box_id;
    fetch_data("get_action_box", true, data, function(content) {
        if (!(jsonObj = json_parse(content))) return;

        // Nie udalo sie prawidlowo pozyskac danych
        if (jsonObj.return_id != "ok") {
            alert(jsonObj.text);
            location.reload();
        }

        action_box.show(jsonObj.template);
    });
}
