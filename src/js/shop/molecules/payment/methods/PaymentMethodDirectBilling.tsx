import React, {FunctionComponent} from "react";
import {__} from "../../../../general/i18n";

interface Props {
    price: string;
}

export const PaymentMethodWallet: FunctionComponent<Props> = (props) => {
    const {price} = props;

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_wallet')}
                    </p>
                </header>
                <div className="card-content">
                    <div className="content"><strong>{__('price')}</strong>: {price}</div>
                </div>
                <footer className="card-footer">
                    <a id="pay_wallet" className="card-footer-item">
                        {__('pay_wallet')}
                    </a>
                </footer>
            </div>
        </div>
    );
}