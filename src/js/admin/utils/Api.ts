import { AxiosInstance } from "axios";
import {
    TemplateLang,
    TemplateCollectionResponse,
    TemplateResourceResponse,
    TemplateTheme,
} from "../types/template";
import { ThemeCollectionResponse } from "../types/theme";

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getThemeList(): Promise<ThemeCollectionResponse> {
        const response = await this.axios.get("/api/admin/themes");
        return response.data;
    }

    public async getTemplateList(
        theme: TemplateTheme,
        lang: TemplateLang
    ): Promise<TemplateCollectionResponse> {
        const response = await this.axios.get(`/api/admin/templates`, { params: { theme, lang } });
        return response.data;
    }

    public async getTemplate(
        name: string,
        theme: TemplateTheme,
        lang: TemplateLang
    ): Promise<TemplateResourceResponse> {
        const encodedName = name.replaceAll("/", "-");

        const response = await this.axios.get(`/api/admin/templates/${encodedName}`, {
            params: { theme, lang },
        });
        return response.data;
    }

    public async putTemplate(
        name: string,
        theme: TemplateTheme,
        lang: TemplateLang,
        content: string
    ): Promise<void> {
        const encodedName = name.replaceAll("/", "-");
        await this.axios.put(
            `/api/admin/templates/${encodedName}`,
            { content },
            { params: { theme, lang } }
        );
    }

    public async deleteTemplate(
        name: string,
        theme: TemplateTheme,
        lang: TemplateLang
    ): Promise<void> {
        const encodedName = name.replaceAll("/", "-");
        await this.axios.delete(`/api/admin/templates/${encodedName}`, { params: { theme, lang } });
    }
}
