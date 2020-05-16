export function get_type_name(value: string): string {
    if (value == "1") {
        return "nick";
    }
    if (value == "2") {
        return "ip";
    }
    if (value == "4") {
        return "sid";
    }

    return "";
}
