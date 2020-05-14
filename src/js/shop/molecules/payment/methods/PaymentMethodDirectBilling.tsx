import React, {FunctionComponent} from "react";
import {__} from "../../../../general/i18n";
import {Dict} from "../../../types/general";
import {PaymentMethod} from "../../../types/transaction";

interface Props {
    price: string;
    onPay(method: PaymentMethod, body?: Dict);
}

export const PaymentMethodDirectBilling: FunctionComponent<Props> = (props) => {
    const {price, onPay} = props;

    const onPayClick = () => onPay(PaymentMethod.DirectBilling);

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
                    <a id="pay_direct_billing" className="card-footer-item" onClick={onPayClick}>
                        {__('pay_direct_billing')}
                    </a>
                </footer>
            </div>
        </div>
    );
}