import { loader } from "../../general/loader";
import { buildUrl, restRequest } from "../../general/global";
import { handleErrorResponse, sthWentWrong } from "../../general/infobox";
import { action_box } from "../../general/action_box";
import { Dict } from "../../shop/types/general";

export const getAndSetTemplate = function(
    element: JQuery,
    template: string,
    data: any,
    onSuccessFunction?
): void {
    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl(`/api/admin/templates/${template}`),
        data: data,
        complete() {
            loader.hide();
        },
        success(content) {
            if (!content) {
                return sthWentWrong();
            }

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

export const refreshAdminContent = () => refreshBlocks(`admincontent:${window.currentPage}`);

export const refreshBlocks = function(bricks: string, onSuccessFunction?: any): void {
    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl(`/api/admin/bricks/${bricks}`),
        data: window.location.search,
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
 * Tworzy okienko akcji danej strony
 *
 * @param {string} pageId
 * @param {string} boxId
 * @param {object} data
 */
export const showActionBox = function(pageId: string, boxId: string, data?: Dict): void {
    restRequest("GET", `/api/admin/pages/${pageId}/action_boxes/${boxId}`, data, function(content) {
        // Nie udalo sie prawidlowo pozyskac danych
        if (content.return_id !== "ok") {
            alert(content.text);
            location.reload();
        }

        action_box.show(content.template);
    });
};

export const clearAndHideActionBox = function(): void {
    action_box.hide();
    $("#action_box_wrapper_td").html("");
};
