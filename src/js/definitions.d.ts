export {};

declare global {
    interface Window {
        currentPage: string;
        baseUrl: string;
        lang: Record<string, any>;
    }
}
