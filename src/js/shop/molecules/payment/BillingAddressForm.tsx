import React, { ChangeEvent, FunctionComponent, useEffect, useState } from "react";
import { __ } from "../../../general/i18n";
import { BillingAddress } from "../../types/transaction";

interface Props {
    address?: BillingAddress;
    onAddressChange(address: BillingAddress): void;
}

export const BillingAddressForm: FunctionComponent<Props> = (props) => {
    const { address, onAddressChange } = props;
    const [billingAddress, setBillingAddress] = useState<BillingAddress>({
        name: address?.name ?? "",
        vatID: address?.vatID ?? "",
        address: address?.address ?? "",
        postalCode: address?.postalCode ?? "",
        city: address?.city ?? "",
    });

    useEffect(() => onAddressChange(billingAddress), [billingAddress]);

    const setName = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, name: e.target.value });
    const setVatID = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, vatID: e.target.value });
    const setAddress = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, address: e.target.value });
    const setPostalCode = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, postalCode: e.target.value });
    const setCity = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, city: e.target.value });

    return (
        <>
            <h3 className="title is-4">Billing Address</h3>
            <div className="columns is-multiline billing-address-form">
                <div className="column is-two-thirds">
                    <div className="field">
                        <label htmlFor="name" className="label required">
                            {__("name")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="name"
                                name="name"
                                className="input"
                                maxLength={128}
                                onChange={setName}
                                value={billingAddress.name}
                                required
                            />
                        </div>
                    </div>
                </div>

                <div className="column">
                    <div className="field">
                        <label htmlFor="vat_id" className="label">
                            {__("vat_id")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="vat_id"
                                name="vat_id"
                                className="input"
                                maxLength={36}
                                onChange={setVatID}
                                value={billingAddress.vatID}
                            />
                        </div>
                    </div>
                </div>

                <div className="column is-full">
                    <div className="field">
                        <label htmlFor="address" className="label required">
                            {__("address")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="address"
                                name="address"
                                className="input"
                                maxLength={128}
                                onChange={setAddress}
                                value={billingAddress.address}
                                required
                            />
                        </div>
                    </div>
                </div>

                <div className="column is-one-third">
                    <div className="field">
                        <label htmlFor="postal_code" className="label required">
                            {__("postal_code")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                className="input"
                                maxLength={16}
                                onChange={setPostalCode}
                                value={billingAddress.postalCode}
                                required
                            />
                        </div>
                    </div>
                </div>

                <div className="column">
                    <div className="field">
                        <label htmlFor="city" className="label required">
                            {__("city")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="city"
                                name="city"
                                className="input"
                                maxLength={128}
                                onChange={setCity}
                                value={billingAddress.city}
                                required
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};
