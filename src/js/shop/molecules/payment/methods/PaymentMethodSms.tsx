import React, {ChangeEvent, FunctionComponent, useState} from "react";
import {__} from "../../../../general/i18n";
import classNames from "classnames";
import {Dict} from "../../../types/general";
import {PaymentMethod} from "../../../types/transaction";
import {PaymentPrice} from "../../../components/PaymentPrice";

interface Props {
    price: string;
    oldPrice?: string;
    smsCode: string;
    smsNumber: string;
    onPay(method: PaymentMethod, body?: Dict);
}

export const PaymentMethodSms: FunctionComponent<Props> = (props) => {
    const {price, oldPrice, smsCode, smsNumber, onPay} = props;
    const [returnCode, setReturnCode] = useState<string>("");
    const [detailsVisible, setDetailsVisible] = useState<boolean>(false);

    const onPayClick = () => {
        if (detailsVisible) {
            onPay(PaymentMethod.Sms, {
                sms_code: returnCode,
            });
        } else {
            setDetailsVisible(true);
        }
    };

    const updateReturnCode = (e: ChangeEvent<HTMLInputElement>) => setReturnCode(e.target.value);

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_sms')}
                    </p>
                </header>
                <div className="card-content">
                    <div>
                        <PaymentPrice price={price} oldPrice={oldPrice} />
                    </div>

                    <div className={classNames("sms-details", {
                        "is-hidden": !detailsVisible,
                    })}>
                        <h1 className="title is-5">{__('sms_send_sms')}</h1>

                        <div className="field is-horizontal">
                            <div className="field-label">
                                <label className="label">{__('sms_text')}</label>
                            </div>
                            <div className="field-body">
                                <span className="is-family-monospace">{smsCode}</span>
                            </div>
                        </div>

                        <div className="field is-horizontal">
                            <div className="field-label">
                                <label className="label">{__('sms_number')}</label>
                            </div>
                            <div className="field-body">
                                <span className="is-family-monospace">{smsNumber}</span>
                            </div>
                        </div>

                        <div className="field is-horizontal">
                            <div className="field-label">
                                <label className="label required" htmlFor="sms_code">
                                    {__('sms_return_code')}
                                </label>
                            </div>
                            <div className="field-body">
                                <div className="field">
                                    <div className="control">
                                        <input
                                            type="text"
                                            id="sms_code"
                                            name="sms_code"
                                            className="input is-small is-family-monospace"
                                            value={returnCode}
                                            onChange={updateReturnCode}
                                            maxLength={16}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer className="card-footer">
                    <a id="pay_sms" className="card-footer-item" onClick={onPayClick}>
                        {__('pay_sms')}
                    </a>
                </footer>
            </div>
        </div>
    );
}