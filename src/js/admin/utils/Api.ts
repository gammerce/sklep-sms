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

    public async getTemplateList(): Promise<TemplateCollectionResponse> {
        const response = await this.axios.get("/api/admin/templates");
        return response.data;
    }

    public async getTemplate(theme: string, name: string): Promise<TemplateResourceResponse> {
        const encodedName = name.replaceAll("/", "-");
        const encodedTheme = encodeURIComponent(theme);
        const response = await this.axios.get(
            `/api/admin/themes/${encodedTheme}/templates/${encodedName}`
        );
        return response.data;
    }

    public async putTemplate(theme: string, name: string, content: string): Promise<void> {
        const encodedName = name.replaceAll("/", "-");
        const encodedTheme = encodeURIComponent(theme);
        const response = await this.axios.put(
            `/api/admin/themes/${encodedTheme}/templates/${encodedName}`,
            { content }
        );
        return response.data;
    }
}
