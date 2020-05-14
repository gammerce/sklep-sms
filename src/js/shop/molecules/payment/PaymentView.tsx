import React, {ChangeEvent, FunctionComponent, useEffect, useState} from "react";
import {__} from "../../../general/i18n";
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

export const PaymentView: FunctionComponent = () => {
    const [transaction, setTransaction] = useState<Transaction>();
    const [promoCode, setPromoCode] = useState<string>("");
    const [appliedPromoCode, setAppliedPromoCode] = useState<string>("");

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
        purchaseService(
            transactionId,
            method,
            {
                ...body,
                promo_code: appliedPromoCode,
            }
        )
            .catch(handleError);
    }

    const updatePromoCode = (e: ChangeEvent<HTMLInputElement>) => setPromoCode(e.target.value);

    const applyPromoCode = async () => {
        loader.show();
        try {
            const result = await api.getTransaction(transactionId, promoCode);
            setTransaction(result);
            setAppliedPromoCode(promoCode);
        } finally {
            loader.hide();
        }
    }

    const removePromoCode = async () => {
        loader.show();
        try {
            const result = await api.getTransaction(transactionId);
            setTransaction(result);
            setAppliedPromoCode("");
            setPromoCode("");
        } finally {
            loader.hide();
        }
    }

    if (!transaction) {
        return <Loader />;
    }

    return (
        <div className="columns">
            <div className="column is-one-third promo-code-box">
                <div className="field">
                    <label className="label" htmlFor="promo_code">{__("promo_code")}</label>
                    <div className="control">
                        <div className="field has-addons">
                            <div className="control">
                                <input
                                    id="promo_code"
                                    className="input"
                                    placeholder={__("type_code")}
                                    value={promoCode}
                                    onChange={updatePromoCode}
                                    disabled={!!appliedPromoCode}
                                />
                            </div>
                            {
                                !appliedPromoCode &&
                                <div className="control">
                                    <button className="button is-primary" onClick={applyPromoCode}>
                                        <span className="icon">
                                            <i className="fas fa-tag" />
                                        </span>
                                        <span>{__("use_code")}</span>
                                    </button>
                                </div>
                            }
                            {
                                appliedPromoCode &&
                                <div className="control">
                                    <button className="button is-primary" onClick={removePromoCode}>
                                        <span className="icon">
                                            <i className="fas fa-trash" />
                                        </span>
                                        <span>{__("remove")}</span>
                                    </button>
                                </div>
                            }

                        </div>
                    </div>
                </div>
            </div>
            <div className="column is-two-thirds">
                <div className="payment-methods-box">
                    {
                        transaction.sms &&
                        <PaymentMethodSms
                            priceGross={transaction.sms.price_gross}
                            smsCode={transaction.sms.sms_code}
                            smsNumber={transaction.sms.sms_number}
                            onPay={onPay}
                        />
                    }
                    {
                        transaction.transfer &&
                        <PaymentMethodTransfer
                            price={transaction.transfer.price}
                            oldPrice={transaction.transfer.old_price}
                            onPay={onPay}
                        />
                    }
                    {
                        transaction.wallet &&
                        <PaymentMethodWallet
                            price={transaction.wallet.price}
                            oldPrice={transaction.wallet.old_price}
                            onPay={onPay}
                        />
                    }
                    {
                        transaction.direct_billing &&
                        <PaymentMethodDirectBilling
                            price={transaction.direct_billing.price}
                            oldPrice={transaction.direct_billing.old_price}
                            onPay={onPay}
                        />
                    }
                </div>
            </div>
        </div>
    );
}
