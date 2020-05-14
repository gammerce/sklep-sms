import { element_with_data_module } from "./global";
import {Dict} from "../shop/types/general";

export const json_parse = function(text: string, show?: boolean): Dict | false {
    show = typeof show !== "undefined" ? show : true;

    try {
        return JSON.parse(text);
    } catch (err) {
        if (show) alert(text);
        return false;
    }
};

export const get_random_string = function(length: number = 8): string {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    let final_rand = "";
    for (let i = 0; i < length; i++) {
        final_rand += chars[Math.floor(Math.random() * (chars.length - 1))];
    }

    return final_rand;
};

/**
 * Sprawdza, czy działa na elemencie stworzonym przez moduł extra_flags
 * Jeżeli tak, to zwraca obiekt najwyżej w drzewie, który został utworzony przez dany moduł
 */
export const service_module_act_can = function(name: string, a: JQuery): JQuery | false {
    const element = element_with_data_module(a);
    return element !== null && element.data("module") == name ? element : false;
};

export const trimSlashes = function(text: string): string {
    return text.replace(/^\/|\/$/g, "");
};
