import React, { FunctionComponent } from "react";
import { __ } from "../../general/i18n";

interface Props {
    price: string;
    oldPrice?: string;
}

export const PaymentPrice: FunctionComponent<Props> = props => {
    const { price, oldPrice } = props;

    return (
        <>
            <strong>{__("price")}</strong>:&nbsp;
            {oldPrice && <s className="old-price">{oldPrice}</s>}&nbsp;
            <span className="price-value">{price}</span>
        </>
    );
};
