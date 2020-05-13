import {Transaction} from "../types/Transaction";
import {buildUrl} from "../../general/global";
import {AxiosInstance} from "axios";

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getTransaction(id: string): Promise<Transaction> {
        const reponse = await this.axios.get(buildUrl(`/api/transactions/${id}`));
        return reponse.data;
    }
}