import { loader } from "../../../general/loader";
import { restRequest, showWarnings } from "../../../general/global";
import { infobox, sthWentWrong } from "../../../general/infobox";
import { refreshBlocks } from "../utils";
import { api } from "../container";

export const purchaseService = async (
    transactionId: string,
    method: string,
    body: Record<string, any> = {}
) => {
    if (loader.blocked) {
        return;
    }

    loader.show();

    const result = await api.makePayment(transactionId, {
        method,
    });

    if (!result || !result.return_id) {
        return sthWentWrong();
    }

    if (result.return_id === "warnings") {
        showWarnings($("#payment"), result.warnings);
    } else if (result.return_id === "purchased") {
        // Update content window with purchase details
        restRequest("GET", `/api/purchases/${result.bsid}`, {}, function(message) {
            $("#page-content").html(message);
        });

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

    infobox.show_info(result.text, result.positive);
};

function redirectToExternalWithPost(jsonObj) {
    const form = $("<form>", {
        action: jsonObj.data.url,
        method: "POST",
    });

    $.each(jsonObj.data, function(key, value) {
        if (key === "url") {
            return true;
        }

        form.append(
            $("<input>", {
                type: "hidden",
                name: key,
                value: value,
            })
        );
    });

    // Bez tego nie dziala pod firefoxem
    $("body").append(form);

    // Wysyłamy formularz zakupu
    form.submit();
}

function redirectToExternalWithGet(jsonObj) {
    const url = jsonObj.data.url;
    delete jsonObj.data.url;

    window.location.href = url + "?" + $.param(jsonObj.data);
}
