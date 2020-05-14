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
        url: buildUrl(`/api/templates/${template}`),
        data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            const jsonObj = json_parse(content);
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

export const refreshBlocks = function(bricks: string, onSuccessFunction?: any) {
    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl(`/api/bricks/${bricks}`),
        data: window.location.search.replace(/^\?/, ""),
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            const jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            $.each(jsonObj, function(brick_id, brick) {
                const brickNode = $(`#${brick_id}`);
                brickNode.html(brick.content);
                brickNode.attr("class", brick.class);
            });

            if (onSuccessFunction) {
                onSuccessFunction(content);
            }
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
export const goToPayment = function(transactionId) {
    window.location.href = buildUrl("/page/payment", { tid: transactionId });
};
