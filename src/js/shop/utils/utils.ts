import { loader } from "../../general/loader";
import { handleErrorResponse } from "../../general/infobox";
import { buildUrl } from "../../general/global";

export const getAndSetTemplate = function(
    element: JQuery,
    template: string,
    data?: any,
    onSuccessFunction?: any
) {
    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl(`/api/templates/${template}`),
        data,
        complete: function() {
            loader.hide();
        },
        success: function(content) {
            if (content.return_id === "no_access") {
                alert(content.text);
                location.reload();
            }

            element.html(content.template);
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
            for (const [brick_id, brick] of content.entries()) {
                const brickNode = $(`#${brick_id}`);
                brickNode.html(brick.content);
                brickNode.attr("class", brick.class);
            }

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
