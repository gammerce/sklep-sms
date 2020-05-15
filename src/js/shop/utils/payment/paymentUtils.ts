import { loader } from "../../../general/loader";
import { removeFormWarnings, showWarnings } from "../../../general/global";
import { infobox, sthWentWrong } from "../../../general/infobox";
import { handleError, refreshBlocks } from "../utils";
import { api } from "../container";
import { Dict } from "../../types/general";
import { PaymentMethod } from "../../types/transaction";

export const purchaseService = async (
    transactionId: string,
    method: PaymentMethod,
    body: Dict = {}
) => {
    if (loader.blocked) {
        return;
    }

    try {
        loader.show();
        await makePayment(transactionId, {
            method,
            ...body,
        });
    } catch (e) {
        sthWentWrong();
    } finally {
        loader.hide();
    }
};

const makePayment = async (transactionId: string, body: Dict): Promise<void> => {
    removeFormWarnings();

    const result = await api.makePayment(transactionId, body);

    if (result.return_id === "warnings") {
        showWarnings($("#payment"), result.warnings);
    } else if (result.return_id === "purchased") {
        // Update content window with purchase details
        api.getPurchase(result.bsid)
            .then(message => $("#page-content").html(message))
            .catch(handleError);

        // Refresh wallet
        refreshBlocks("wallet", function() {
            const wallet = $("#wallet");
            if ((wallet as any).effect) {
                (wallet as any).effect("highlight", "slow");
            }
        });
    } else if (result.return_id === "external") {
        const method = result.data.method;
        delete result.data.method;

        if (method === "GET") {
            redirectToExternalWithGet(result);
        } else if (method === "POST") {
            redirectToExternalWithPost(result);
        } else {
            console.error("Invalid method specified by PaymentModule");
            sthWentWrong();
            return;
        }
    }

    infobox.showInfo(result.text, result.positive);
};

const redirectToExternalWithPost = (response: any) => {
    const form = $("<form>", {
        action: response.data.url,
        method: "POST",
    });

    for (const [key, value] of Object.entries(response.data)) {
        if (key !== "url") {
            form.append(
                $("<input>", {
                    type: "hidden",
                    name: key,
                    value: value,
                })
            );
        }
    }

    // It doesn't work with firefox without it
    $("body").append(form);

    form.submit();
};

const redirectToExternalWithGet = (response: any) => {
    const url = response.data.url;
    delete response.data.url;
    window.location.href = url + "?" + $.param(response.data);
};
