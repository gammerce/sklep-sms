import React, { ChangeEvent, FunctionComponent, useEffect, useState } from "react";
import { __ } from "../../../general/i18n";
import { BillingAddress } from "../../types/transaction";
import { FormError } from "../../components/FormError";
import { Dict } from "../../types/general";

interface Props {
    address?: BillingAddress;
    errors?: Dict;
    onAddressChange(address: BillingAddress): void;
}

export const BillingAddressForm: FunctionComponent<Props> = (props) => {
    const { address, errors, onAddressChange } = props;
    const [billingAddress, setBillingAddress] = useState<BillingAddress>({
        name: address?.name ?? "",
        vat_id: address?.vat_id ?? "",
        address: address?.address ?? "",
        postal_code: address?.postal_code ?? "",
        city: address?.city ?? "",
    });

    useEffect(() => onAddressChange(billingAddress), [billingAddress]);

    const setName = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, name: e.target.value });
    const setVatID = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, vat_id: e.target.value });
    const setAddress = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, address: e.target.value });
    const setPostalCode = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, postal_code: e.target.value });
    const setCity = (e: ChangeEvent<HTMLInputElement>) =>
        setBillingAddress({ ...billingAddress, city: e.target.value });

    return (
        <>
            <h3 className="title is-4">Billing Address</h3>
            <div className="columns is-multiline billing-address-form">
                <div className="column is-two-thirds">
                    <div className="field">
                        <label htmlFor="billing_address_name" className="label required">
                            {__("name")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="billing_address_name"
                                name="billing_address_name"
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
                        <label htmlFor="billing_address_vat_id" className="label">
                            {__("vat_id")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="billing_address_vat_id"
                                name="billing_address_vat_id"
                                className="input"
                                maxLength={128}
                                onChange={setVatID}
                                value={billingAddress.vat_id}
                            />
                        </div>
                        <FormError errors={errors?.["business_address.vat_id"]} />
                    </div>
                </div>

                <div className="column is-full">
                    <div className="field">
                        <label htmlFor="billing_address_address" className="label required">
                            {__("address")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="billing_address_address"
                                name="billing_address_address"
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
                        <label htmlFor="billing_address_postal_code" className="label required">
                            {__("postal_code")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="billing_address_postal_code"
                                name="billing_address_postal_code"
                                className="input"
                                maxLength={128}
                                onChange={setPostalCode}
                                value={billingAddress.postal_code}
                                required
                            />
                        </div>
                    </div>
                </div>

                <div className="column">
                    <div className="field">
                        <label htmlFor="billing_address_city" className="label required">
                            {__("city")}
                        </label>
                        <div className="control">
                            <input
                                type="text"
                                id="billing_address_city"
                                name="billing_address_city"
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
