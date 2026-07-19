# Core hardening (Iteration 20+)

Produkčná bezpečnosť a prevádzka jadra PaginiumCMS. Aktuálny stav: **release 2.0.26**.

| Iterácia | Téma | Dokument |
|----------|------|----------|
| It. 20 | RBAC, maintenance, trash | tento súbor §1–§6 |
| It. 41+ | OTP workflow (publish, komentáre) | [API_CONTRACT.md](./API_CONTRACT.md) §2.7 |
| It. 50 | WAF (interný firewall) | §7, [user/FIREWALL.md](../user/FIREWALL.md) |
| 2.0.26 | Structured logging, HTTP access log | §8, [user/LOGGING.md](../user/LOGGING.md) |

---

## 1. RBAC na mutáciách

| Endpoint | Oprávnenie | Role s prístupom |
|----------|------------|------------------|
| `POST /api/pages`, `POST /api/articles` | `content:create` | EDITOR, ADMIN, SUPER_ADMIN |
| `PUT/PATCH /api/pages|articles/*` | `content:edit` | EDITOR, ADMIN, SUPER_ADMIN |
| `DELETE /api/pages|articles/*` | `content:delete` | EDITOR, ADMIN, SUPER_ADMIN |
| `GET /api/media` | rola | EDITOR, ADMIN, SUPER_ADMIN |
| `POST/PATCH /api/media/*` | `media:upload` | EDITOR, ADMIN, SUPER_ADMIN |
| `DELETE /api/media/*` | `media:delete` | EDITOR, ADMIN, SUPER_ADMIN |
| `/api/admin/firewall/*` | rola | ADMIN, SUPER_ADMIN |
| `/api/admin/logs/*` | rola | ADMIN, SUPER_ADMIN |

Rola **USER** má len `content:view` – zápis vracia **403**.

ADMIN má `content:manage` / `media:manage`, čo pokrýva všetky akcie v doméne.

---

## 2. Servovanie médií

- URL v médiách: `/storage/app/content/media/{súbor}`
- Backend route: `GET /storage/{path}` → `backend/storage/{path}`
- Dev: Vite proxy `/storage` → backend
- Produkcia: nginx alias na `backend/storage/` alebo rovnaká Slim route
- SVG/HTML: `Content-Disposition: attachment` + CSP sandbox (audit S2)

---

## 3. Režim údržby

Nastavenie `general.maintenanceMode = true`:

- Verejné API (`/api/pages`, `/api/search`, …) → **503** + `{ maintenance: true }`
- Výnimky: `/api/admin/*`, `/api/auth/*`, `/api/health`, `/api/settings/public`, `/storage/*`
- Prihlásený EDITOR/ADMIN/SUPER_ADMIN môže naďalej volať API (náhľad, editácia)

---

## 4. Registrácia a komentáre

- `general.allowRegistration = false` → `POST /api/auth/register` → **403**
- `comments.allowGuestComments = false` → neauth `POST /api/comments` → **403**
- Per-article override: pozri [CONTENT_API.md](./CONTENT_API.md)

---

## 5. Session fixation

`SessionManager::setUser()` volá `session_regenerate_id(true)` pri každom prihlásení.

---

## 6. Trash (soft-delete)

Pri `FileWriter::delete(..., moveToTrash: true)`:

1. Súbor sa presunie do `content/trash/{timestamp}_{name}`
2. Sidecar `{timestamp}_{name}.meta.json` obsahuje `id`, `originalPath`, `deletedAt`

API:

- `GET /api/admin/trash` – zoznam položiek
- `POST /api/admin/trash/{id}/restore` – obnova + rebuild indexu
- Bulk: `bulk-restore`, `bulk-purge`, `bulk-backup`, `empty`

Frontend: `TrashManager` na `/trash`.

---

## 7. WAF — Web Application Firewall (It. 50)

**Middleware:** `FirewallMiddleware` — globálne, **pred** `RateLimitMiddleware`.

Tok:

1. Whitelist IP → prepustiť
2. Ban/jail → **403** (plain text, nie JSON — pozri API_CONTRACT §2.8)
3. Match scenára (SQLi, path traversal, …) → incident log + eskalácia banu → **403**
4. Inak → normálne spracovanie

| Komponent | Úloha |
|-----------|--------|
| `FirewallService` | Orchestrácia scan + ban |
| `FirewallScanner` | Pattern matching |
| `FirewallBanStore` | Flat-file bans, SIN scores |
| `FirewallIncidentLogger` | Posledné incidenty |
| `FirewallController` | Admin API |

Nastavenia: skupina `firewall` v Settings. Admin UI: `/firewall`.

**Testing:** WAF je vypnutý pri `APP_ENV=testing` (PHPUnit).

**Proxy:** Nastav `TRUSTED_PROXIES` v `backend/.env` — IP za nginx sa inak berie ako proxy IP.

---

## 8. Structured logging (2.0.26)

| Vrstva | Služba | Výstup |
|--------|--------|--------|
| HTTP access | `RequestLoggingMiddleware` | `storage/logs/app/` — každý endpoint, IP, duration |
| Aggregácia | `ApplicationLogReader` | Admin `GET /api/admin/logs` |
| Audit / event / user | existujúce log writery | `storage/logs/{audit,event,user}/` |

Nastavenia: skupina `logging` (`requestLogging`, `minSeverity`, `retentionDays`, `slowRequestMs`, `logAuthEndpoints`).

Admin UI: `/logs`, dashboard panel severity (24 h).

---

## 9. Rate limiting a CI

- `RateLimitMiddleware` — globálny limit; **early return** pri `APP_ENV=testing` (ISS-015)
- `LoginRateLimitMiddleware` — `/api/auth/login`
- PHPUnit `TestCase` resetuje settings, OTP store, rate-limit cache medzi testami

---

## 10. Backup cron

```bash
# Každú hodinu (príklad crontab)
0 * * * * cd /path/to/project && php backend/bin/console backup:run-schedule
```

Plán sa ukladá cez `BackupManager::scheduleBackup()` do `storage/backups/schedule.json`.

---

## Frontend (prehľad)

| Funkcia | Cesta / komponent |
|---------|-------------------|
| Náhľad draftu | verejné routes v `App.tsx` |
| Staff-only admin | `AdminRoleGuard` |
| Verzie v editore | `VersionHistory` v `MarkdownEditor` |
| Dev logy | `/developer/logs` |
| WAF admin | `/firewall` — `FirewallManager` |
| App logy | `/logs` — `LogsManager` |
| Kôš | `/trash` — `TrashManager` |

---

## Súvisiace dokumenty

- [BACKEND.md](./BACKEND.md) — middleware poradie
- [CORE.md](./CORE.md) — mapa Core balíkov
- [ISSUES.md](../ISSUES.md) — CI incidenty ISS-015–022
