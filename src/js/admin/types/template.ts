export interface Template {
    name: string;
    deletable: boolean;
}

export interface TemplateCollectionResponse {
    data: Template[];
}

export interface TemplateResourceResponse {
    name: string;
    content: string;
}
