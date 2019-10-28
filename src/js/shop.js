require("../stylesheets/shop.scss");
require("./partials/global.js");
require("./partials/stocks.js");
require("./partials/window.js");
require("./partials/loader.js");
require("./partials/infobox.js");

$(document).ready(function() {
    if (typeof f !== "undefined") $(".content_td").append(atob(f));

    /*$("#bck").bind('input',function() {
     $("body").css({"background-image":"url('"+$(this).val()+"')"});
     });*/
    $("#language_" + language).addClass("current");
});

/**
 * Go to payment page
 */
window.go_to_payment = function(data, sign) {
    var form = $("<form>", {
        action: buildUrl("/page/payment"),
        method: "POST",
    });

    // Add data
    form.append(
        $("<input>", {
            type: "hidden",
            name: "data",
            value: data,
        })
    );

    // Sign data
    form.append(
        $("<input>", {
            type: "hidden",
            name: "sign",
            value: sign,
        })
    );

    // Required for firefox
    $("body").append(form);

    // Send purchase form
    form.submit();
};

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
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id == "logged_in") {
                $("#user_buttons").css({ overflow: "hidden" }); // Hide login area
                refresh_blocks(
                    "logged_info,wallet,user_buttons,services_buttons" +
                        ($("#form_login_reload_content").val() == "0" ? "" : ",content")
                );
            }
            if (jsonObj.return_id == "already_logged_in") {
                location.reload();
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
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
            if (!(jsonObj = json_parse(content))) return;

            if (jsonObj.return_id == "logged_out") {
                refresh_blocks("logged_info,wallet,user_buttons,services_buttons,content");
            }
            if (jsonObj.return_id == "already_logged_out") {
                location.reload();
            } else if (!jsonObj.return_id) {
                infobox.show_info(lang["sth_went_wrong"], false);
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
        },
        error: function(error) {
            infobox.show_info(lang["ajax_error"], false);
        },
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

// Choosing a language
$(document).delegate("#language_choice img", "click", function() {
    var langClicked = $(this)
        .attr("id")
        .replace("language_", "");

    rest_request("PUT", "/api/session/language", false, { language: langClicked }, function() {
        location.reload();
    });
});
