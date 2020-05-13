import React, {FunctionComponent} from "react";
import {__} from "../../../../general/i18n";

interface Props {
    priceGross: string;
    smsCode: string;
    smsNumber: string;
}

export const PaymentMethodSms: FunctionComponent<Props> = (props) => {
    const {priceGross, smsCode, smsNumber} = props;

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

                    <div id="sms_details" className="has-text-left is-hidden">
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
                                            maxLength={16}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer className="card-footer">
                    <a id="pay_sms" className="card-footer-item">
                        {__('pay_sms')}
                    </a>
                </footer>
            </div>
        </div>
    );
}