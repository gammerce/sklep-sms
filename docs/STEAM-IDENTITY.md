# Steam identity — design note

Status: **proposal** (to be agreed before implementation).

## Background / findings

The Steam ID string is **not** one thing. What we actually receive:

| Source | Format | Evidence |
|---|---|---|
| Steam OpenID (`claimed_id`) | **SteamID64** (17 digits) | `SteamOpenId`, `SteamIDConverter` |
| SourceMod (Source), `/api/server/*` | **Steam2** `STEAM_x:y:z` via `AuthId_Steam2`, sent in `Authorization: Bearer …` and `auth_data` | `plugin-sourcemod/.../shopsms.sp` |
| AMXX (GoldSrc) | **Steam2** `get_user_authid` → `STEAM_0:y:z` | `gammerce-amxx/.../shopsms.sma` |

The `X` in `STEAM_X:Y:Z` is the **universe** (0 = individual/legacy, 1 = public).
The SteamID64 formula `76561197960265728 + 2·Z + Y` **ignores `X`**, so
`STEAM_0:Y:Z` and `STEAM_1:Y:Z` map to the **same** SteamID64.

The SourceMod plugin even papers over this by duplicating entries, replacing
`STEAM_0` → `STEAM_1` (`server.inc`). Conclusion: **a Steam2 string is never a
safe identity key.** SteamID64 is the only unambiguous one.

## Decision

- Store **one canonical form in the DB: SteamID64** (single column, `ss_users.steam_id`).
- **Transform at the boundary**: on write (any input format → SteamID64),
  on read (SteamID64 → the format the caller/UI/plugin needs).
- Keep `steam_verified` (a boolean flag, **not** a second ID) = "proven via
  Steam OpenID" vs "typed in manually".

No second ID column. One canonical value + a conversion layer.

## Conversion layer

Single source of truth, shared by web + server API + converters + templates:

- Accept on input: SteamID64, `STEAM_[0-5]:[01]:\d+`, `[U:1:\d+]`.
- Canonical stored value: SteamID64.
- Canonical legacy output for display/plugins: `STEAM_0:Y:Z`.
- Ignore the universe on input (it does not change the account).
- Reject/normalise the dead `STEAM_2..5` prefixes.

Implement in `App\Support\SteamIDConverter` (PHP) and `src/js/general/steam_id.ts` (JS),
and route **all** inputs through it.

## Auth flows (after the change)

- **Web (OpenID):** `claimed_id` → SteamID64 → match/create by canonical column.
- **In-game (`/api/server/*`):** `Authorization: Bearer <Steam2>` →
  `SteamIDConverter` → SteamID64 → match by canonical column → wallet.
  (`AuthorizeServerUser` currently uses `findBySteamId`.)
- **Service targeting (ExtraFlags):** unchanged — `ss_user_service_extra_flags.auth_data`
  keeps the legacy `STEAM_0` string that plugins already exchange.

Because the stored key is universe-independent and unique, the previous 0/1
ambiguity (wrong wallet charged, non-deterministic `fetch()`, duplicate-key
`500`) disappears.

## Schema

- `ss_users.steam_id` — now holds the **canonical SteamID64** (keep `VARCHAR(32)`
  or shrink to `CHAR(17)`); `UNIQUE` stays (now enforces one account per Steam
  account, universe-independent).
- `ss_users.steam_verified` — unchanged meaning (OpenID proof).

## Migration (careful)

1. Convert `steam_id` in place: legacy → SteamID64 (idempotent: a value that is
   already 64 digits is left as is).
2. **Detect collisions**: two rows that were `STEAM_0:…` / `STEAM_1:…` of the
   same account collapse to one SteamID64 → unique violation. Policy: keep the
   "better" row (verified, more services), set the other's `steam_id = NULL` and
   **log for manual merge**.
3. Unparseable values → left as is + logged.
4. The existing `UNIQUE(steam_id)` index then holds.

Run before serving; the installer applies migrations before requests.

### Read/write boundary
- `UserRepository::mapToModel()`: stored SteamID64 → legacy `STEAM_0` for the `User` model.
- `UserRepository::create()/update()/linkSteamId()`: input → SteamID64 for storage.
- `UserRepository::findBySteamId()`: input → SteamID64 → `WHERE steam_id = ?`.

The rest of the app (controllers, templates, plugins) keeps working with the
legacy representation it already uses — only the repository boundary changes.

### Admin search
`PageAdminUsers` searches `steam_id` with `LIKE`. Since the column now holds
SteamID64, searching by a legacy string wouldn't match. Mitigation: if the search
term parses as a Steam ID, convert it to SteamID64 as well (or add both forms to
the search).

## Decision log

1. **Wallet in-game auth: B** — a manually typed link still authorises in-game
   wallet. Safe because the game server reports the **real** SteamID of the
   connected player; the residual abuse (squatting a public SID) is covered by
   (a) web login matching **verified** links only, and (b) verified-beats-unverified
   takeover below.
2. **Manual Steam ID entry stays.**
3. **Auto-create** account on first Steam login stays.
4. **Verified beats unverified**: connecting via OpenID reclaims a Steam ID held
   by an unverified (manually typed) claim on another account; the loser is
   unlinked and the event is logged. A conflicting **verified** link is refused.

## Status

Implemented: single canonical column (`ss_users.steam_id` holds SteamID64),
read/write conversion at the repository boundary, web + in-game matching by the
canonical ID, verified-beats-unverified takeover, and a data migration that
canonicalises existing rows and resolves 0/1 collisions.

Plugins are untouched: they keep sending Steam2; we canonicalise on our side.
