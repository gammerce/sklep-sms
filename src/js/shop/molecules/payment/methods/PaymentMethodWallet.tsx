import React, {FunctionComponent} from "react";
import {__} from "../../../../general/i18n";

interface Props {
    price: string;
}

export const PaymentMethodDirectBilling: FunctionComponent<Props> = (props) => {
    const {price} = props;

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_direct_billing')}
                    </p>
                </header>
                <div className="card-content">
                    <div className="content"><strong>{__('price')}</strong>: {price}<br/></div>
                </div>
                <footer className="card-footer">
                    <a id="pay_direct_billing" className="card-footer-item">
                        {__('pay_direct_billing')}
                    </a>
                </footer>
            </div>
        </div>
    );
}