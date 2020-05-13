import React, {FunctionComponent, useEffect, useState} from "react";
import {__} from "../../../general/i18n";
import {Transaction} from "../../types/Transaction";
import {api} from "../../utils/container";
import {Loader} from "../../components/Loader";
import {PaymentMethodSms} from "./methods/PaymentMethodSms";
import {PaymentMethodTransfer} from "./methods/PaymentMethodTransfer";
import {PaymentMethodDirectBilling} from "./methods/PaymentMethodWallet";
import {PaymentMethodWallet} from "./methods/PaymentMethodDirectBilling";

interface State {
    transaction?: Transaction;
}

export const PaymentView: FunctionComponent = () => {
    const [data, setData] = useState<State>({ transaction: undefined });
    const queryParams = new URLSearchParams(window.location.search);
    const transactionId = queryParams.get("tid");

    useEffect(
        () => {
            api.getTransaction(transactionId)
                .then(transaction => setData({transaction}))
                .catch(console.error);
        },
        []
    );

    if (!data.transaction) {
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
                                />
                            </div>
                            <div className="control">
                                <button className="button is-primary">
                                    {__("use_code")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="column is-two-thirds">
                <div className="payment-methods-box">
                    {
                        data.transaction.sms &&
                        <PaymentMethodSms
                            priceGross={data.transaction.sms.price_gross}
                            smsCode={data.transaction.sms.sms_code}
                            smsNumber={data.transaction.sms.sms_number}
                        />
                    }
                    {
                        data.transaction.transfer &&
                        <PaymentMethodTransfer price={data.transaction.transfer.price}/>
                    }
                    {
                        data.transaction.wallet &&
                        <PaymentMethodWallet price={data.transaction.wallet.price}/>
                    }
                    {
                        data.transaction.direct_billing &&
                        <PaymentMethodDirectBilling price={data.transaction.direct_billing.price}/>
                    }
                </div>
            </div>
        </div>
    );
}
