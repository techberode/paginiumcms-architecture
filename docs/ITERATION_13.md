# Iteration 13 – Demo Sandbox (demo.paginiumcms.com only)

**Status:** ✅ Complete (2.0.28)  
**Version:** 2.0.28

## Produktová pozícia (dôležité)

**Demo modul nie je súčasť štandardného produkčného balíka PaginiumCMS.**

Metafora: **predvádzacie vozidlo / trenažér** — nedá sa kúpiť, ale na ňom si vyskúšaš, čo produkt dokáže. Po jazde sa „resetuje“ a ide ďalšiemu záujemcovi.

| Nasadenie | Demo modul |
|-----------|------------|
| Zákaznícka produkcia (`klient.sk`, …) | ❌ **Nikdy** — `DEMO_MODE=false`, bez `/demo` v prevádzke |
| Vlastná inštancia **demo.paginiumcms.com** | ✅ Jediný účel — plnohodnotné vyskúšanie CMS pred kúpou |

Cieľ: návštevník z hlavnej domény skúsi admin + verejný web na subdoméne; zmeny sú dočasné (periodický reset); produkčný obsah zákazníkov sa nedotkne.

→ Celá produktová filozofia: [PHILOSOPHY.md](PHILOSOPHY.md)

## Summary

Izolované demo prostredie s `DEMO_MODE`. **v2:** celý CMS (obsah, users, settings) beží z `storage/app/demo/`; auto-reset cron; demo login UI.

## Delivered (v1 + v2)

| Deliverable | Status |
|-------------|--------|
| `DEMO_MODE` env flag | ✅ |
| `DemoMode` + `DemoStorageService` | ✅ |
| **Content base path switch** (FileValidator → demo/) | ✅ v2 |
| **Full snapshot seed** (pages, articles, index, user, settings, nav) | ✅ v2 |
| **Auto-reset cron** `demo:reset-if-due` | ✅ v2 |
| **Dlhá session** `SESSION_LIFETIME` | ✅ v2 |
| **Demo login guard** | ✅ jasné hlášky pri zámene demo/produkčného účtu |
| **Marketing link** footer → demo.paginiumcms.com | ✅ v2 |
| Separate storage `storage/app/demo/` | ✅ |
| Seed pages + articles (`DemoFixtures::seedFiles()`) | ✅ |
| MOCK comments/messages/newsletter via `DemoDataProvider` | ✅ |
| `GET /api/admin/demo/status` | ✅ |
| `POST /api/admin/demo/reset` (SUPER_ADMIN only) | ✅ |
| Public settings `demo.enabled` | ✅ |
| Admin banner + `/demo` manager UI | ✅ |
| PHPUnit isolation + controller smoke | ✅ |

## Backend

```
Modules/Demo/Services/DemoMode.php
Modules/Demo/Services/DemoStorageService.php
Modules/Demo/Services/DemoDataProvider.php
Modules/Demo/Data/DemoFixtures.php
Modules/Demo/Services/DemoResetScheduler.php
Modules/Demo/Commands/RunDemoResetCommand.php
Http/Controllers/Admin/DemoController.php
Http/Routes/demo.php
```

| Route | Auth | Notes |
|-------|------|-------|
| `GET /api/admin/demo/status` | ADMIN + 2FA | enabled, paths, file_count |
| `POST /api/admin/demo/reset` | SUPER_ADMIN + 2FA | Re-seed demo files |

**Activation:** `DEMO_MODE=true` v `.env` (koreň projektu **alebo** `backend/.env`) + **reštart PHP**. Overenie: `GET /api/settings/public` → `demo.enabled: true`.

## Frontend

- `frontend/src/api/demo.ts`
- `DemoModeBanner` in admin shell (when `demo.enabled`)
- `DemoManager` at `/demo` — status + reset

## Tests

| Suite | File |
|-------|------|
| PHPUnit | `DemoStorageServiceTest`, `DemoResetSchedulerTest`, `DemoModeTest` |
| PHPUnit | `DemoControllerTest`, `DemoDataProviderTest` |

## Cron (demo inštancia)

```bash
# každých 15 min — obnova snapshotu
*/15 * * * * cd /path/to/project && php backend/bin/console demo:reset-if-due
```

## Demo credentials

| Pole | Hodnota |
|------|---------|
| E-mail | `demo@paginiumcms.com` |
| Heslo | `Demo123!` |

## Nasadenie (len demo subdoména)

```env
# IBA na demo.paginiumcms.com — NIKDY na zákazníckej produkcii
DEMO_MODE=true
APP_URL=https://demo.paginiumcms.com
VITE_PUBLIC_URL=https://demo.paginiumcms.com
SESSION_LIFETIME=14400
DEMO_AUTO_RESET_MINUTES=60
```

Samostatný Docker/stack alebo compose profil `demo` — rovnaký kód, iné `.env` oproti `paginiumcms.com`.

## Dependencies (met)

- ✅ Iteration 19 – content repository abstraction

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 13
- [Modules/Demo/README.md](../backend/app/Modules/Demo/README.md)

## Next

→ [Iteration 14](ITERATION_14.md) – Code policy engine
