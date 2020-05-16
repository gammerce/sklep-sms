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
import {AxiosError} from "axios";
import {showWarnings} from "../../../general/global";
import {infobox} from "../../../general/infobox";

export const PaymentView: FunctionComponent = () => {
    const [transaction, setTransaction] = useState<Transaction>();

    const queryParams = new URLSearchParams(window.location.search);
    const transactionId = queryParams.get("tid");

    useEffect(
        () => {
            fetchTransaction().catch(handleError);
        },
        []
    );

    const fetchTransaction = async () => {
        const result = await api.getTransaction(transactionId);
        setTransaction(result);
    };

    const onPay = (method: PaymentMethod, body: Dict): void => {
        purchaseService(transactionId, method, body).catch(handleError);
    };

    const applyPromoCode = async (promoCode: string) => {
        loader.show();
        try {
            const result = await api.applyPromoCode(transactionId, promoCode);
            setTransaction(result);
        } catch (error) {
            const e: AxiosError = error;

            if (e.response.status === 422) {
                showWarnings($("#payment"), e.response.data.warnings);
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
                        sms &&
                        <PaymentMethodSms
                            price={sms.price}
                            oldPrice={sms.old_price}
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
