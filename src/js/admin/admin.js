import "../../stylesheets/admin.scss";

import "core-js";
import {changeUrl} from "../general/global";

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
$(document).delegate(".table-structure .search", "submit", function(e) {
    e.preventDefault();

    changeUrl({
        search: $(this)
            .find(".search_text")
            .val(),
        page: "",
    });
});

$(document).delegate(".dropdown", "click", function() {
    $(this).toggleClass("is-active");
});

document.addEventListener("click", function(e) {
    // Do not remove class if user clicked dropdown element
    if (!$(e.target).closest(".dropdown").length) {
        $(".dropdown").removeClass("is-active");
    }
});
