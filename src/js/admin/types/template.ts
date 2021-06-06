export interface Template {
    name: string;
}

export interface TemplateCollectionResponse {
    data: Template[];
}

export interface TemplateResourceResponse {
    name: string;
    content: string;
}
