import { Transaction } from "../types/Transaction";
import { buildUrl } from "../../general/global";
import { AxiosInstance } from "axios";

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getTransaction(id: string): Promise<Transaction> {
        const reponse = await this.axios.get(buildUrl(`/api/transactions/${id}`));
        return reponse.data;
    }

    public async makePayment(transactionId: string, body: any): Promise<any> {
        const response = await this.axios.post(buildUrl(`/api/payment/${transactionId}`), body);
        return response.data;
    }
}
