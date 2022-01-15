export enum PaymentMethod {
    DirectBilling = "direct_billing",
    Sms = "sms",
    Transfer = "transfer",
    Wallet = "wallet",
}

export type PaymentOption =
    | DirectBillingPaymentOption
    | SmsPaymentOption
    | TransferPaymentOption
    | WalletPaymentOption;

interface BasePaymentOption {
    method: PaymentMethod;
    payment_platform_id?: number;
    name?: string;
    details: any;
}

export interface DirectBillingPaymentOption extends BasePaymentOption {
    method: PaymentMethod.DirectBilling;
    payment_platform_id: number;
    name: undefined;
    details: {
        price: string;
        old_price?: string;
    };
}

export interface SmsPaymentOption extends BasePaymentOption {
    method: PaymentMethod.Sms;
    payment_platform_id: number;
    name: undefined;
    details: {
        price: string;
        old_price?: string;
        sms_code: string;
        sms_number: string;
    };
}

export interface TransferPaymentOption extends BasePaymentOption {
    method: PaymentMethod.Transfer;
    payment_platform_id: number;
    name: string;
    details: {
        price: string;
        old_price?: string;
    };
}

export interface WalletPaymentOption extends BasePaymentOption {
    method: PaymentMethod.Wallet;
    payment_platform_id: undefined;
    name: undefined;
    details: {
        price: string;
        old_price?: string;
    };
}

export interface Transaction {
    promo_code?: string;
    payment_options: Array<PaymentOption>;
    supports_billing_address: boolean;
}

export interface BillingAddress {
    name: string;
    vat_id: string;
    street: string;
    postal_code: string;
    city: string;
}
