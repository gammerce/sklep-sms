import { loader } from "../../../general/loader";
import { removeFormWarnings, showWarnings } from "../../../general/global";
import { infobox, sthWentWrong } from "../../../general/infobox";
import { handleError, refreshBlocks } from "../utils";
import { api } from "../container";
import { Dict } from "../../types/general";
import { BillingAddress, PaymentMethod } from "../../types/transaction";

export const purchaseService = async (
    transactionId: string,
    method: PaymentMethod,
    paymentPlatformId: number | undefined,
    billingAddress: BillingAddress | undefined,
    rememberBillingAddress: boolean | undefined,
    body: Dict = {}
) => {
    if (loader.blocked) {
        return;
    }

    try {
        loader.show();
        await makePayment(transactionId, {
            method,
            payment_platform_id: paymentPlatformId,
            billing_address_name: billingAddress?.name,
            billing_address_vat_id: billingAddress?.vat_id,
            billing_address_postal_code: billingAddress?.postal_code,
            billing_address_street: billingAddress?.street,
            billing_address_city: billingAddress?.city,
            remember_billing_address: rememberBillingAddress,
            ...body,
        });
    } catch (e) {
        console.error(e);
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
            .then((message) => $("#page-content").html(message))
            .catch(handleError);

        // Refresh wallet
        refreshBlocks("wallet", function () {
            const wallet = $("#wallet");
            if ((wallet as any).effect) {
                (wallet as any).effect("highlight", "slow");
            }
        });
    } else if (result.return_id === "external") {
        const method = result.data.method;

        if (method === "GET") {
            redirectToExternalWithGet(result.data);
        } else if (method === "POST") {
            redirectToExternalWithPost(result.data);
        } else {
            console.error("Invalid method specified by PaymentModule");
            sthWentWrong();
            return;
        }
    }

    infobox.showInfo(result.text, result.positive);
};

const redirectToExternalWithPost = (result: any) => {
    const form = $("<form>", {
        action: result.url,
        method: "POST",
    });

    for (const [key, value] of Object.entries(result.data)) {
        form.append(
            $("<input>", {
                type: "hidden",
                name: key,
                value: value,
            })
        );
    }

    // It doesn't work with firefox without it
    $("body").append(form);

    form.submit();
};

const redirectToExternalWithGet = (result: any) => {
    const query = $.param(result.data || {});
    const url = result.url + (query ? `?${query}` : "");

    const aNode = document.createElement("a");
    aNode.href = url;
    document.body.appendChild(aNode);
    aNode.click();
};
