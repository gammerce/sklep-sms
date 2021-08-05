import { loader } from "./loader";

export const onKeyPress = (checkKey: (e: KeyboardEvent) => boolean, onPressed: () => void) => {
    const handleDown = (event) => {
        if (checkKey(event)) {
            event.preventDefault();
            onPressed();
        }
    };

    window.addEventListener("keydown", handleDown);

    return () => {
        window.removeEventListener("keydown", handleDown);
    };
};

export const toggleLoader = (value: boolean): void => {
    if (value) {
        loader.show();
    } else {
        loader.hide();
    }
};
