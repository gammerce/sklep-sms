require("../stylesheets/shop.scss");
require("./partials/global.js");
require("./partials/stocks.js");
require("./partials/window.js");
require("./partials/loader.js");
require("./partials/infobox.js");
require("./pages/profile.js");

$(document).ready(function() {
    if (typeof f !== "undefined") $(".content_td").append(atob(f));

    /*$("#bck").bind('input',function() {
     $("body").css({"background-image":"url('"+$(this).val()+"')"});
     });*/
    $("#language_" + language).addClass("current");
});

window.getAndSetTemplate = function(element, template, data, onSuccessFunction) {
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl("/api/templates/" + template),
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
        url: buildUrl("/api/bricks/" + bricks) + "?" + query,
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
            var jsonObj = json_parse(content);

            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "logged_in") {
                $("#user_buttons").css({ overflow: "hidden" }); // Hide login area
                refresh_blocks(
                    "logged_info,wallet,user_buttons,services_buttons" +
                        ($("#form_login_reload_content").val() == "0" ? "" : ",content")
                );
            }

            if (jsonObj.return_id === "already_logged_in") {
                location.reload();
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
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
            var jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            if (!jsonObj.return_id) {
                return sthWentWrong();
            }

            if (jsonObj.return_id === "logged_out") {
                refresh_blocks("logged_info,wallet,user_buttons,services_buttons,content");
            }
            if (jsonObj.return_id === "already_logged_out") {
                location.reload();
            }

            infobox.show_info(jsonObj.text, jsonObj.positive);
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

// Choosing a language
$(document).delegate("#language_choice img", "click", function() {
    var langClicked = $(this)
        .attr("id")
        .replace("language_", "");

    restRequest("PUT", "/api/session/language", { language: langClicked }, function() {
        location.reload();
    });
});
