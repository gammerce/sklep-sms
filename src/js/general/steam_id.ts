class SteamIDConverter {
    private readonly BASE_NUM = BigInt("76561197960265728"); // "V" in the conversion algorithms
    private readonly REGEX_STEAMID64 = /^[0-9]{17}$/;
    private readonly REGEX_STEAMID = /^STEAM_[0-5]:[01]:\d+$/;
    private readonly REGEX_STEAMID3 = /^\[U:1:[0-9]+\]$/;

    /**
     * Convert any format to SteamID64
     */
    public toSteamID64(value: string): string {
        if (this.isSteamID64(value)) {
            return value;
        }

        const steamID = this.toSteamID(value),
            split = steamID.split(":"),
            v = this.BASE_NUM,
            z = BigInt(split[2]),
            y = BigInt(split[1]);

        return (v + z * BigInt(2) + y).toString();
    }

    /**
     * Convert any format to SteamID
     */
    public toSteamID(value: string): string {
        if (this.isSteamID(value)) {
            return value;
        }

        if (this.isSteamID3(value)) {
            const split = value.split(":"),
                last = Number(split[2].substring(0, split[2].length - 1)),
                y = last % 2,
                z = Math.floor(last / 2);

            return `STEAM_0:${y}:${z}`;
        }

        if (this.isSteamID64(value)) {
            const v = this.BASE_NUM,
                w = BigInt(value),
                y = BigInt(Number(w.toString().slice(-1)) % 2),
                z = (w - y - v) / BigInt(2);

            return `STEAM_0:${y}:${z}`;
        }

        throw new TypeError("Invalid value format");
    }

    /**
     * Convert any format to SteamID3
     */
    public toSteamID3(value: string): string {
        if (this.isSteamID3(value)) {
            return value;
        }

        const steamID = this.toSteamID(value);
        const split = steamID.split(":");

        return "[U:1:" + (parseInt(split[1]) + parseInt(split[2]) * 2) + "]";
    }

    public isSteamID(value: string): boolean {
        return this.REGEX_STEAMID.test(value);
    }

    public isSteamID64(value: string): boolean {
        return this.REGEX_STEAMID64.test(value);
    }

    public isSteamID3(value: string): boolean {
        return this.REGEX_STEAMID3.test(value);
    }

    public profileURL(value: string): string {
        return `https://steamcommunity.com/profiles/${this.toSteamID64(value)}`;
    }
}
