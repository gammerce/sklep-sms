import { Transaction } from "../types/transaction";
import { buildUrl } from "../../general/global";
import { AxiosInstance } from "axios";

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getTransaction(id: string, promoCode?: string): Promise<Transaction> {
        const params = { promo_code: promoCode };
        const reponse = await this.axios.get(buildUrl(`/api/transactions/${id}`), { params });
        return reponse.data;
    }

    public async applyPromoCode(transactionId: string, promoCode: string): Promise<Transaction> {
        const reponse = await this.axios.post(
            buildUrl(`/api/transactions/${transactionId}/promo_code/${promoCode}`)
        );
        return reponse.data;
    }

    public async unsetPromoCode(transactionId: string): Promise<Transaction> {
        const reponse = await this.axios.delete(
            buildUrl(`/api/transactions/${transactionId}/promo_code`)
        );
        return reponse.data;
    }

    public async makePayment(transactionId: string, body: any): Promise<any> {
        const response = await this.axios.post(buildUrl(`/api/payment/${transactionId}`), body);
        return response.data;
    }

    public async getPurchase(boughtServiceId: number): Promise<string> {
        const response = await this.axios.get(`/api/purchases/${boughtServiceId}`);
        return response.data;
    }
}
