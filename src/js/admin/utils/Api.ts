import { AxiosInstance } from "axios";
import { TemplateCollectionResponse, TemplateResourceResponse } from "../types/template";
import { ThemeCollectionResponse } from "../types/theme";

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getThemeList(): Promise<ThemeCollectionResponse> {
        const response = await this.axios.get("/api/admin/themes");
        return response.data;
    }

    public async getThemeTemplateList(): Promise<TemplateCollectionResponse> {
        const response = await this.axios.get("/api/admin/theme-templates");
        return response.data;
    }

    public async getThemeTemplate(id: string): Promise<TemplateResourceResponse> {
        const name = id.replaceAll("/", "-");
        const response = await this.axios.get(`/api/admin/theme-templates/${name}`);
        return response.data;
    }
}
