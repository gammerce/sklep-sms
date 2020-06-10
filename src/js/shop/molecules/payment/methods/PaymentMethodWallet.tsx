import React, { FunctionComponent } from "react";
import { __ } from "../../../../general/i18n";
import { Dict } from "../../../types/general";
import { PaymentPrice } from "../../../components/PaymentPrice";

interface Props {
    price: string;
    oldPrice?: string;
    onPay(body?: Dict): void;
}

export const PaymentMethodWallet: FunctionComponent<Props> = (props) => {
    const {price, oldPrice, onPay} = props;

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_wallet')}
                    </p>
                </header>
                <div className="card-content">
                    <PaymentPrice price={price} oldPrice={oldPrice} />
                </div>
                <footer className="card-footer">
                    <a id="pay_wallet" className="card-footer-item" onClick={onPay}>
                        {__('pay_wallet')}
                    </a>
                </footer>
            </div>
        </div>
    );
}
