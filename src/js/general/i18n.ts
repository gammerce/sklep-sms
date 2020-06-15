const sprintf = (text: string, ...args: any[]): string => {
    for (const [index, arg] of args.entries()) {
        text = text.replace(`{${index + 1}}`, arg);
    }

    return text;
};

export const __ = (key: string, ...args: any[]): string => sprintf(window.lang[key] ?? key, args);
