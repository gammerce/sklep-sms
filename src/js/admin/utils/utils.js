import { loader } from "../../general/loader";
import { buildUrl, restRequest } from "../../general/global";
import { json_parse } from "../../general/stocks";
import { handleErrorResponse } from "../../general/infobox";
import { action_box } from "../../general/action_box";

export const getAndSetTemplate = function(element, template, data, onSuccessFunction) {
    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    loader.show();

    $.ajax({
        type: "GET",
        url: buildUrl("/api/admin/templates/" + template),
        data: data,
        complete() {
            loader.hide();
        },
        success(content) {
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
        error(error) {
            handleErrorResponse();
            location.reload();
        },
    });
};

export const refreshAdminContent = () => refreshBlocks(`admincontent:${currentPage}`);

export const refreshBlocks = function(bricks, onSuccessFunction) {
    loader.show();

    onSuccessFunction =
        typeof onSuccessFunction !== "undefined" ? onSuccessFunction : function() {};

    $.ajax({
        type: "GET",
        url: buildUrl(`/api/admin/bricks/${bricks}`),
        data: window.location.search,
        complete() {
            loader.hide();
        },
        success(content) {
            const jsonObj = json_parse(content);
            if (!jsonObj) {
                return;
            }

            $.each(jsonObj, function(brick_id, brick) {
                const brickNode = $(`#${brick_id}`);
                brickNode.html(brick.content);
                brickNode.attr("class", brick.class);
            });

            onSuccessFunction(content);
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
export const showActionBox = function(pageId, boxId, data) {
    restRequest("GET", `/api/admin/pages/${pageId}/action_boxes/${boxId}`, data, function(content) {
        const jsonObj = json_parse(content);
        if (!jsonObj) {
            return;
        }

        // Nie udalo sie prawidlowo pozyskac danych
        if (jsonObj.return_id !== "ok") {
            alert(jsonObj.text);
            location.reload();
        }

        action_box.show(jsonObj.template);
    });
};

export const clearAndHideActionBox = function() {
    action_box.hide();
    $("#action_box_wrapper_td").html("");
};
