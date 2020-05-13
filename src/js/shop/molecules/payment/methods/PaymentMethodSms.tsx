import React, {ChangeEvent, FunctionComponent, useState} from "react";
import {__} from "../../../../general/i18n";
import {purchaseService} from "../../../utils/payment/paymentUtils";
import classNames from "classnames";

interface Props {
    priceGross: string;
    smsCode: string;
    smsNumber: string;
}

interface State {
    smsCode: string;
    showDetails: boolean;
}

export const PaymentMethodSms: FunctionComponent<Props> = (props) => {
    const [data, setData] = useState<State>({
        smsCode: "",
        showDetails: false,
    });
    const {priceGross, smsCode, smsNumber} = props;

    const onPayClick = () => {
        if (data.showDetails) {
            purchaseService("a", "sms", {
                sms_code: data.smsCode
            });
        } else {
            setData(state => ({
                ...state,
                showDetails: true
            }));
        }
    };

    const updateSmsCode = (e: ChangeEvent<HTMLInputElement>) => {
        setData(state => ({
            ...state,
            smsCode: e.target.value
        }));
    }

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
                        <strong>{__('price')}</strong>:&nbsp;
                        <span className="is-family-monospace">
                            {priceGross}
                        </span>
                    </div>

                    <div className={classNames("sms-details", {
                        "is-hidden": !data.showDetails,
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
                                            value={data.smsCode}
                                            onChange={updateSmsCode}
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