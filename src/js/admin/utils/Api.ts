import { AxiosInstance } from "axios";
import { Lang, TemplateCollectionResponse, TemplateResourceResponse } from "../types/template";
import { ThemeCollectionResponse } from "../types/theme";

const prepareTemplateUrl = (theme: string, name: string, lang: Lang): string => {
    const encodedName = name.replaceAll("/", "-");
    const encodedTheme = encodeURIComponent(theme);

    if (lang === null) {
        return `/api/admin/themes/${encodedTheme}/templates/${encodedName}`;
    } else {
        return `/api/admin/themes/${encodedTheme}/templates/${encodedName}/languages/${lang}`;
    }
};

export class Api {
    public constructor(private readonly axios: AxiosInstance) {
        //
    }

    public async getThemeList(): Promise<ThemeCollectionResponse> {
        const response = await this.axios.get("/api/admin/themes");
        return response.data;
    }

    public async getTemplateList(theme: string, lang: Lang): Promise<TemplateCollectionResponse> {
        const response = await this.axios.get(
            `/api/admin/themes/${encodeURIComponent(theme)}/templates`,
            { params: { lang } }
        );
        return response.data;
    }

    public async getTemplate(
        theme: string,
        name: string,
        lang: Lang
    ): Promise<TemplateResourceResponse> {
        const response = await this.axios.get(prepareTemplateUrl(theme, name, lang));
        return response.data;
    }

    public async putTemplate(
        theme: string,
        name: string,
        lang: Lang,
        content: string
    ): Promise<void> {
        await this.axios.put(prepareTemplateUrl(theme, name, lang), { content });
    }

    public async deleteTemplate(theme: string, name: string, lang: Lang): Promise<void> {
        await this.axios.delete(prepareTemplateUrl(theme, name, lang));
    }
}
