import { loader } from "../../general/loader";
import { handleErrorResponse } from "../../general/infobox";
import { buildUrl } from "../../general/global";
import { Dict } from "../types/general";

export const handleError = (e: Error) => console.error(e);

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
        complete() {
            loader.hide();
        },
        success(content) {
            if (content.return_id === "no_access") {
                alert(content.text);
                location.reload();
            }

            element.html(content.template);

            if (onSuccessFunction) {
                onSuccessFunction();
            }
        },
        error(error) {
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
        complete() {
            loader.hide();
        },
        success(content: Dict) {
            for (const [brick_id, brick] of Object.entries(content)) {
                const brickNode = $(`#${brick_id}`);
                brickNode.html(brick.content);
                brickNode.attr("class", brick.class);
            }

            if (onSuccessFunction) {
                onSuccessFunction(content);
            }
        },
        error(error) {
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
