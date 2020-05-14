export enum PaymentMethod {
    DirectBilling = "direct_billing",
    Sms = "sms",
    Transfer = "transfer",
    Wallet = "wallet",
}

export interface Transaction {
    direct_billing?: {
        price: string;
    };
    sms?: {
        price_gross: string;
        sms_code: string;
        sms_number: string;
    };
    transfer?: {
        price: string;
    };
    wallet?: {
        price: string;
    };
}
