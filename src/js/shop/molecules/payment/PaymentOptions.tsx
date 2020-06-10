import React, { FunctionComponent } from "react";
import { PaymentMethod, PaymentOption as IPaymentOption } from "../../types/transaction";
import { PaymentMethodSms } from "./methods/PaymentMethodSms";
import { PaymentMethodDirectBilling } from "./methods/PaymentMethodDirectBilling";
import { PaymentMethodWallet } from "./methods/PaymentMethodWallet";
import { PaymentMethodTransfer } from "./methods/PaymentMethodTransfer";
import { Dict } from "../../types/general";

interface Props {
    paymentOption: IPaymentOption;
    onPay(body?: Dict): void;
}

export const PaymentOption: FunctionComponent<Props> = (props) => {
    const {paymentOption, onPay} = props;

    if (paymentOption.method === PaymentMethod.DirectBilling) {
        return (
            <PaymentMethodDirectBilling
                price={paymentOption.details.price}
                oldPrice={paymentOption.details.old_price}
                onPay={onPay}
            />
        );
    }

    if (paymentOption.method === PaymentMethod.Sms) {
        return (
            <PaymentMethodSms
                price={paymentOption.details.price}
                oldPrice={paymentOption.details.old_price}
                smsCode={paymentOption.details.sms_code}
                smsNumber={paymentOption.details.sms_number}
                onPay={onPay}
            />
        );
    }

    if (paymentOption.method === PaymentMethod.Transfer) {
        return (
            <PaymentMethodTransfer
                name={paymentOption.name}
                price={paymentOption.details.price}
                oldPrice={paymentOption.details.old_price}
                onPay={onPay}
            />
        );
    }

    if (paymentOption.method === PaymentMethod.Wallet) {
        return (
            <PaymentMethodWallet
                price={paymentOption.details.price}
                oldPrice={paymentOption.details.old_price}
                onPay={onPay}
            />
        );
    }

    return null;
};
