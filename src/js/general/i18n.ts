const sprintf = (text: string, ...args: string[]): string => {
    for (const [index, arg] of args.entries()) {
        text = text.replace(`{${index + 1}}`, arg);
    }

    return text;
};

export const __ = (key: string, ...args: string[]): string =>
    sprintf(window.lang[key] ?? key, ...args);
