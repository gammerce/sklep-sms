import { AxiosInstance } from "axios";
import { TemplateCollectionResponse, TemplateResourceResponse } from "../types/template";
import { ThemeCollectionResponse } from "../types/theme";

const prepareTemplateUrl = (theme: string, name: string): string => {
    const encodedName = name.replaceAll("/", "-");
    const encodedTheme = encodeURIComponent(theme);
    return `/api/admin/themes/${encodedTheme}/templates/${encodedName}`;
};

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getThemeList(): Promise<ThemeCollectionResponse> {
        const response = await this.axios.get("/api/admin/themes");
        return response.data;
    }

    public async getTemplateList(theme: string): Promise<TemplateCollectionResponse> {
        const response = await this.axios.get(prepareTemplateUrl(theme, ""));
        return response.data;
    }

    public async getTemplate(theme: string, name: string): Promise<TemplateResourceResponse> {
        const response = await this.axios.get(prepareTemplateUrl(theme, name));
        return response.data;
    }

    public async putTemplate(theme: string, name: string, content: string): Promise<void> {
        await this.axios.put(prepareTemplateUrl(theme, name), { content });
    }

    public async deleteTemplate(theme: string, name: string): Promise<void> {
        await this.axios.delete(prepareTemplateUrl(theme, name));
    }
}
