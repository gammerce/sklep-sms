export interface Template {
    name: string;
    deletable: boolean;
}

export type TemplateLang = string | null;
export type TemplateTheme = string | null;

export interface TemplateCollectionResponse {
    data: Template[];
}

export interface TemplateResourceResponse {
    name: string;
    content: string;
}
