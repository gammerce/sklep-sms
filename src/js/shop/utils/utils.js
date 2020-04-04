import { loader } from "../../general/loader";
import { json_parse } from "../../general/stocks";
import { handleErrorResponse } from "../../general/infobox";
import { buildUrl } from "../../general/global";

export const getAndSetTemplate = function(element, template, data, onSuccessFunction) {
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

export const refreshBlocks = function(bricks, onSuccessFunction) {
    loader.show();

    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    var splittedUrl = document.URL.split("?");
    var query = splittedUrl.length > 1 ? splittedUrl.pop() : "";

    $.ajax({
        type: "GET",
        url: buildUrl("/api/bricks/" + bricks),
        data: query,
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
export const goToPayment = function(data, sign) {
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
