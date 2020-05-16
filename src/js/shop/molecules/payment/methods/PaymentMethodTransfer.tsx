import React, {FunctionComponent} from "react";
import {__} from "../../../../general/i18n";
import {Dict} from "../../../types/general";
import {PaymentMethod} from "../../../types/transaction";
import {PaymentPrice} from "../../../components/PaymentPrice";

interface Props {
    price: string;
    oldPrice?: string;
    onPay(method: PaymentMethod, body?: Dict);
}

export const PaymentMethodTransfer: FunctionComponent<Props> = (props) => {
    const {price, oldPrice, onPay} = props;

    const onPayClick = () => onPay(PaymentMethod.Transfer);

    return (
        <div className="payment-type-wrapper">
            <div className="card">
                <header className="card-header">
                    <p className="card-header-title">
                        {__('payment_transfer')}
                    </p>
                </header>
                <div className="card-content">
                    <PaymentPrice price={price} oldPrice={oldPrice} />
                </div>
                <footer className="card-footer">
                    <a id="pay_transfer" className="card-footer-item" onClick={onPayClick}>
                        {__('pay_transfer')}
                    </a>
                </footer>
            </div>
        </div>
    );
}