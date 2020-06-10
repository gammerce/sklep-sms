import React, { FunctionComponent } from "react";
import { __ } from "../../../../general/i18n";
import { Dict } from "../../../types/general";
import { PaymentPrice } from "../../../components/PaymentPrice";

interface Props {
    name: string;
    price: string;
    oldPrice?: string;
    onPay(body?: Dict);
}

export const PaymentMethodTransfer: FunctionComponent<Props> = (props) => {
    const {price, oldPrice, name, onPay} = props;

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_transfer', name)}
                    </p>
                </header>
                <div className="card-content">
                    <PaymentPrice price={price} oldPrice={oldPrice} />
                </div>
                <footer className="card-footer">
                    <a id="pay_transfer" className="card-footer-item" onClick={onPay}>
                        {__('pay_transfer')}
                    </a>
                </footer>
            </div>
        </div>
    );
}
