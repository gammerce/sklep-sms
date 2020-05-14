import "core-js";
import { refreshBlocks } from "./utils/utils";
import { loader } from "../general/loader";
import { buildUrl, restRequest } from "../general/global";
import { handleErrorResponse, infobox, sthWentWrong } from "../general/infobox";

$(document).ready(function() {
    if (window.f) {
        $(".f-placement").append(atob(window.f));
    }

    $("#language_" + window.language).addClass("is-active");
});

// Login
$(document).delegate("#form_login", "submit", function(e) {
    e.preventDefault();
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("/api/login"),
        data: $(this).serialize(),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "logged_in") {
                if (window.location.pathname.endsWith("/page/login")) {
                    window.location.href = buildUrl("/");
                } else {
                    $("#user_buttons").css({ overflow: "hidden" }); // Hide login area
                    refreshBlocks(
                        `logged_info,wallet,user_buttons,services_buttons,content:${window.currentPage}`
                    );
                }
            }

            if (content.return_id === "already_logged_in") {
                location.reload();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Logout
$(document).delegate("#logout", "click", function(e) {
    loader.show();

    $.ajax({
        type: "POST",
        url: buildUrl("/api/logout"),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (!content.return_id) {
                return sthWentWrong();
            }

            if (content.return_id === "logged_out") {
                refreshBlocks(
                    `logged_info,wallet,user_buttons,services_buttons,content:${window.currentPage}`
                );
            }
            if (content.return_id === "already_logged_out") {
                location.reload();
            }

            infobox.showInfo(content.text, content.positive);
        },
        error: handleErrorResponse,
    });
});

// Expand the login form
$(document).delegate("#loginarea_roll_button", "click", function() {
    var area = $(".loginarea");
    $(".loginarea table")
        .stop()
        .animate(
            {
                marginLeft: "0px",
            },
            500,
            function() {
                $("#user_buttons").css({
                    overflow: area.css("overflow") != "hidden" ? "hidden" : "visible",
                });
                $(".loginarea table")
                    .stop()
                    .animate(
                        {
                            marginLeft: "-220px",
                        },
                        500
                    );
            }
        );
});

// Choose a language
$(document).delegate(".language-item", "click", function() {
    var langClicked = $(this)
        .attr("id")
        .replace("language_", "");

    restRequest("PUT", "/api/session/language", { language: langClicked }, function() {
        location.reload();
    });
});

$(document).delegate(".navbar-burger", "click", function() {
    $(this).toggleClass("is-active");
    $(".navbar-menu").toggleClass("is-active");
});
