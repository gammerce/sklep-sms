import React, { FunctionComponent, useEffect, useState } from "react";
import { BillingAddress, PaymentMethod, Transaction } from "../../types/transaction";
import { api } from "../../utils/container";
import { Loader } from "../../components/Loader";
import { Dict } from "../../types/general";
import { purchaseService } from "../../utils/payment/paymentUtils";
import { handleError } from "../../utils/utils";
import { loader } from "../../../general/loader";
import { PromoCodeBox } from "./PromoCodeBox";
import { AxiosError } from "axios";
import { infobox } from "../../../general/infobox";
import { __ } from "../../../general/i18n";
import { PaymentOption } from "./PaymentOptions";
import { BillingAddressForm } from "./BillingAddressForm";

export const PaymentView: FunctionComponent = () => {
    const [transaction, setTransaction] = useState<Transaction>();
    const [billingAddress, setBillingAddress] = useState<BillingAddress>();

    const queryParams = new URLSearchParams(window.location.search);
    const transactionId = queryParams.get("tid");

    useEffect(() => {
        fetchTransaction().catch(handleError);
    }, []);

    const fetchTransaction = async () => {
        const result = await api.getTransaction(transactionId);
        setTransaction(result);
    };

    const onPay = (method: PaymentMethod, paymentPlatformId?: number, body: Dict = {}): void => {
        purchaseService(transactionId, method, paymentPlatformId, billingAddress, body).catch(
            handleError
        );
    };

    const applyPromoCode = async (promoCode: string) => {
        loader.show();
        try {
            const result = await api.applyPromoCode(transactionId, promoCode);
            setTransaction(result);
        } catch (error) {
            const e: AxiosError = error;

            if (e.response.status === 422) {
                return infobox.showInfo(
                    e.response.data.warnings?.promo_code ?? __("sth_went_wrong"),
                    false
                );
            }

            infobox.showInfo(e.response.data.text, false);
        } finally {
            loader.hide();
        }
    };

    const removePromoCode = async () => {
        loader.show();
        try {
            const result = await api.unsetPromoCode(transactionId);
            setTransaction(result);
        } finally {
            loader.hide();
        }
    };

    if (!transaction) {
        return <Loader />;
    }

    const acceptsPromoCode = transaction.promo_code !== undefined;
    const supportsBillingAddress = transaction.supports_billing_address;

    const paymentOptions = transaction.payment_options.map((paymentOption) => (
        <PaymentOption
            key={`${paymentOption.method}#${paymentOption.payment_platform_id}`}
            paymentOption={paymentOption}
            onPay={(body) => onPay(paymentOption.method, paymentOption.payment_platform_id, body)}
        />
    ));

    return (
        <form id="payment-form">
            {supportsBillingAddress && <BillingAddressForm onAddressChange={setBillingAddress} />}

            <h3 className="title is-4">{__("payment_method")}</h3>
            <div className="columns">
                {acceptsPromoCode && (
                    <div className="column is-one-third">
                        <PromoCodeBox
                            promoCode={transaction.promo_code}
                            onPromoCodeApply={applyPromoCode}
                            onPromoCodeRemove={removePromoCode}
                        />
                    </div>
                )}
                <div className="column">
                    <div className="payment-options-box">{paymentOptions}</div>
                </div>
            </div>
        </form>
    );
};
