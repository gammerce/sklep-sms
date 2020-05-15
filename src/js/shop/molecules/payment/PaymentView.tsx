import React, {FunctionComponent, useEffect, useState} from "react";
import {PaymentMethod, Transaction} from "../../types/transaction";
import {api} from "../../utils/container";
import {Loader} from "../../components/Loader";
import {PaymentMethodSms} from "./methods/PaymentMethodSms";
import {PaymentMethodTransfer} from "./methods/PaymentMethodTransfer";
import {PaymentMethodDirectBilling} from "./methods/PaymentMethodDirectBilling";
import {PaymentMethodWallet} from "./methods/PaymentMethodWallet";
import {Dict} from "../../types/general";
import {purchaseService} from "../../utils/payment/paymentUtils";
import {handleError} from "../../utils/utils";
import {loader} from "../../../general/loader";
import {PromoCodeBox} from "./PromoCodeBox";

export const PaymentView: FunctionComponent = () => {
    const [transaction, setTransaction] = useState<Transaction>();

    const queryParams = new URLSearchParams(window.location.search);
    const transactionId = queryParams.get("tid");

    // TODO Handle API errors

    useEffect(
        () => {
            api.getTransaction(transactionId)
                .then(setTransaction)
                .catch(handleError);
        },
        []
    );

    const onPay = (method: PaymentMethod, body: Dict): void => {
        purchaseService(transactionId, method, body).catch(handleError);
    }


    const applyPromoCode = async (promoCode: string) => {
        loader.show();
        try {
            const result = await api.applyPromoCode(transactionId, promoCode);
            setTransaction(result);
        } finally {
            loader.hide();
        }
    }

    const removePromoCode = async () => {
        loader.show();
        try {
            const result = await api.unsetPromoCode(transactionId);
            setTransaction(result);
        } finally {
            loader.hide();
        }
    }

    if (!transaction) {
        return <Loader />;
    }

    const {sms, transfer, direct_billing, wallet} = transaction.payment_methods;
    const acceptsPromoCode = transaction.promo_code !== undefined;

    return (
        <div className="columns">
            {
                acceptsPromoCode &&
                <div className="column is-one-third">
                    <PromoCodeBox
                        promoCode={transaction.promo_code}
                        onPromoCodeApply={applyPromoCode}
                        onPromoCodeRemove={removePromoCode}
                    />
                </div>
            }
            <div className="column">
                <div className="payment-methods-box">
                    {
                        sms && acceptsPromoCode &&
                        <PaymentMethodSms
                            priceGross={sms.price_gross}
                            smsCode={sms.sms_code}
                            smsNumber={sms.sms_number}
                            onPay={onPay}
                        />
                    }
                    {
                        transfer &&
                        <PaymentMethodTransfer
                            price={transfer.price}
                            oldPrice={transfer.old_price}
                            onPay={onPay}
                        />
                    }
                    {
                        wallet &&
                        <PaymentMethodWallet
                            price={wallet.price}
                            oldPrice={wallet.old_price}
                            onPay={onPay}
                        />
                    }
                    {
                        direct_billing &&
                        <PaymentMethodDirectBilling
                            price={direct_billing.price}
                            oldPrice={direct_billing.old_price}
                            onPay={onPay}
                        />
                    }
                </div>
            </div>
        </div>
    );
}
