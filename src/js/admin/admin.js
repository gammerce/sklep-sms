import "../../stylesheets/admin/admin.scss";

import "core-js";
import { changeUrl } from "../general/global";

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

$(document).delegate(".dropdown", "click", function(e) {
    e.stopImmediatePropagation();
    $(".dropdown").not(this).removeClass("is-active");
    $(this).toggleClass("is-active");
});

$(document).delegate("#navbar-burger", "click", function() {
    $(this).toggleClass("is-active");
    $(".navbar-menu").toggleClass("is-active");
});

$(document).delegate("#sidebar-burger", "click", function(e) {
    e.stopImmediatePropagation();
    $(this).toggleClass("is-active");
    $(".sidebar-menu").toggleClass("is-active");
    $("#overlay").toggleClass("is-active");
});

document.addEventListener("click", function(e) {
    // Close all dropdowns
    $(".dropdown").removeClass("is-active");

    // Do not hide sidebar if user clicks sidebar
    if (!$(e.target).closest(".sidebar-menu").length) {
        $("#sidebar-burger").removeClass("is-active");
        $(".sidebar-menu").removeClass("is-active");
        $("#overlay").removeClass("is-active");
    }
});
