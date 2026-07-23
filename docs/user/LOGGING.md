# Logy — príručka administrátora

> **Release 2.0.26** — structured logy, HTTP access logging, admin prehliadka.

## Čo sa loguje

| Zdroj | Priečinok | Obsah |
|-------|-----------|--------|
| `app` | `storage/logs/app/` | HTTP requesty (`http_access`), všeobecné app logy |
| `audit` | `storage/logs/audit/` | Audit trail (obsah, login, roly) |
| `event` | `storage/logs/event/` | Systémové udalosti (backup, útoky) |
| `user` | `storage/logs/user/` | Aktivita používateľov |

Každý záznam obsahuje **timestamp**, **severity**, **IP** (ak je známa), kategóriu, správu a voliteľný **context** (JSON).

## HTTP access logging

Middleware `RequestLoggingMiddleware` loguje každý API endpoint:

- **Timestamp** — pole `timestamp` + `context.timestamp_utc`
- **IP** — skutočná klientska IP (s `TRUSTED_PROXIES` za nginx)
- **Method, path, status, duration_ms**
- **User ID** — ak je request autentifikovaný

Mapovanie severity podľa HTTP status:

| Status | Severity |
|--------|----------|
| 2xx | `info` |
| 3xx | `debug` |
| 4xx | `warning` |
| 5xx | `error` |
| Pomalý request (> slowRequestMs) | `warning` |

Auth cesty (`/api/auth/login`, …) sa **defaultne nelogujú** — zapnite v Nastaveniach → Logy → „Logovať auth endpointy“.

## Kde to nájsť v admin

| Miesto | URL |
|--------|-----|
| Prehľad severity (24 h) | **Dashboard** → panel Logy |
| Detail + filtre | **Admin → Logy** (`/logs`) |
| Nastavenia | **Nastavenia → Logy** |

Filtrovanie: severity (debug/info/warning/error/critical), zdroj (app/audit/event/user), fulltext search, stav (aktívne/archivované), stránkovanie s ručným počtom záznamov na stránku (1–500).

**Bulk akcie (2.0.52):** zaškrtnite riadky → **Archivovať** alebo **Vymazať**. **Vymazať všetko** zmaže všetky log súbory naraz (potvrdenie v dialógu). **Purge starých** stále používa `retentionDays` z nastavení.

## Nastavenia (skupina `logging`)

| Kľúč | Default | Popis |
|------|---------|--------|
| `enabled` | `true` | Master prepínač |
| `requestLogging` | `true` | HTTP access log pre všetky endpointy |
| `minSeverity` | `debug` | Nižšie úrovne sa neukladajú |
| `retentionDays` | `30` | Retencia denných JSON súborov |
| `slowRequestMs` | `2000` | Prah pomalého requestu |
| `logAuthEndpoints` | `false` | Login/register metadata |

## Admin API

| Metóda | Endpoint |
|--------|----------|
| GET | `/api/admin/logs/stats?hours=24` |
| GET | `/api/admin/logs?severity=&source=&search=&archived=&limit=&offset=` — odpoveď obsahuje `total` |
| POST | `/api/admin/logs/purge` — vymaže súbory staršie ako `retentionDays` |
| POST | `/api/admin/logs/bulk` — `{ "ids": ["log_…"], "action": "delete"|"archive" }` |
| POST | `/api/admin/logs/delete-all` — vymaže všetky log súbory (všetky zdroje) |

Role: **ADMIN+**, 2FA.

## Súvisiace

- [FIREWALL.md](FIREWALL.md) — WAF incidenty (samostatný register)
- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — index dokumentácie
- `TRUSTED_PROXIES` v `.env` — správna IP v logoch za nginx ([FIREWALL.md](FIREWALL.md#odporúčaná-prevádzka))
