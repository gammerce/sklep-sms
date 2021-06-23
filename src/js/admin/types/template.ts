export interface Template {
    name: string;
    deletable: boolean;
}

export type Lang = string | null;

export interface TemplateCollectionResponse {
    data: Template[];
}

export interface TemplateResourceResponse {
    name: string;
    content: string;
}
