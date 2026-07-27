# Iteration 13 – Demo Sandbox (demo.paginiumcms.com only)

**Status:** ✅ Complete (v3 shipped)  
**Version:** 2.0.28 + **v3 polish** (beta.9+)

## Produktová pozícia (dôležité)

**Demo modul nie je súčasť štandardného produkčného balíka PaginiumCMS.**

Metafora: **predvádzacie vozidlo / trenažér** — nedá sa kúpiť, ale na ňom si vyskúšaš, čo produkt dokáže. Po jazde sa „resetuje“ a ide ďalšiemu záujemcovi.

| Nasadenie | Demo modul |
|-----------|------------|
| Zákaznícka produkcia (`klient.sk`, …) | ❌ **Nikdy** — `DEMO_MODE=false`, bez `/demo` v prevádzke |
| Vlastná inštancia **demo.paginiumcms.com** | ✅ Jediný účel — plnohodnotné vyskúšanie CMS pred kúpou |
| Marketing **paginiumcms.com** | ✅ Footer odkaz + Settings → Marketing (bez demo modulu) |

Cieľ: návštevník z hlavnej domény skúsi admin + verejný web na subdoméne; zmeny sú dočasné (periodický reset); produkčný obsah zákazníkov sa **nikdy** nedotkne.

→ Celá produktová filozofia: [PHILOSOPHY.md](PHILOSOPHY.md)

## Summary

Izolované demo prostredie s `DEMO_MODE`. **v3:** plný CMS trial — kompletný seed (komentáre, správy, newsletter, kontakt), countdown resetu, verejný demo pás, marketing settings na prod.

## Delivered (v1 + v2 + v3)

| Deliverable | Status |
|-------------|--------|
| `DEMO_MODE` env flag | ✅ |
| `DemoMode` + `DemoStorageService` | ✅ |
| **Content base path switch** (FileValidator → demo/) | ✅ v2 |
| **Full snapshot seed** (pages, articles, index, user, settings, nav) | ✅ v2 |
| **Rich seed v3** (comments, messages, newsletter, contact page, appearance) | ✅ v3 |
| **Auto-reset cron** `demo:reset-if-due` | ✅ v2 |
| **Dlhá session** `SESSION_LIFETIME` | ✅ v2 |
| **Demo login guard** | ✅ |
| **Marketing link** footer → demo (Settings → Marketing on prod) | ✅ v3 |
| **Public demo strip** + countdown | ✅ v3 |
| **Admin `/demo`** onboarding + reset countdown (ADMIN+) | ✅ v3 |
| `GET /api/demo/public-info` | ✅ v3 |
| Separate storage `storage/app/demo/` | ✅ |
| `GET /api/admin/demo/status` + `POST …/reset` | ✅ |
| Public settings `demo.enabled` / credentials on demo instance | ✅ |
| Admin banner + `/demo` manager UI | ✅ |
| PHPUnit isolation + controller smoke | ✅ |

## Backend

```
Modules/Demo/Services/DemoMode.php
Modules/Demo/Services/DemoStorageService.php
Modules/Demo/Data/DemoFixtures.php
Modules/Demo/Services/DemoResetScheduler.php
Modules/Demo/Commands/RunDemoResetCommand.php
Http/Controllers/Admin/DemoController.php
Http/Routes/demo.php
```

| Route | Auth | Notes |
|-------|------|-------|
| `GET /api/demo/public-info` | Public | enabled, next reset — no secrets |
| `GET /api/admin/demo/status` | ADMIN + 2FA | paths, file count, schedule |
| `POST /api/admin/demo/reset` | ADMIN + 2FA | Re-seed demo files |

**Activation:** `DEMO_MODE=true` v `.env` + **reštart PHP**. Overenie: `GET /api/settings/public` → `demo.enabled: true`.

**Isolation:** `DemoStorageService::assertIsolatedFromProduction()` — demo path must not overlap `storage/app/content/`.

## Frontend

- `frontend/src/api/demo.ts` — status, publicInfo, reset
- `DemoModeBanner` — countdown + link `/demo`
- `DemoManager` at `/demo` — onboarding, schedule, reset (ADMIN+)
- `DemoPublicStrip` — verejný pás na demo inštancii
- `LoginModal` — fill demo credentials
- Settings group **Marketing** — footer demo URL + toggle (prod only)

## Tests

| Suite | File |
|-------|------|
| PHPUnit | `DemoStorageServiceTest`, `DemoResetSchedulerTest`, `DemoModeTest`, `DemoFixturesTest` |
| PHPUnit | `DemoControllerTest`, `DemoDataProviderTest`, `DemoLoginIsolationTest` |

## Cron (demo inštancia)

```bash
*/15 * * * * cd /path/to/project && php backend/bin/console demo:reset-if-due
```

## Demo credentials

| Pole | Hodnota |
|------|---------|
| E-mail | `demo@paginiumcms.com` |
| Heslo | `Demo123!` |

## Nasadenie (len demo subdoména)

```env
DEMO_MODE=true
APP_URL=https://demo.paginiumcms.com
VITE_PUBLIC_URL=https://demo.paginiumcms.com
SESSION_LIFETIME=14400
DEMO_AUTO_RESET_MINUTES=60
```

**Kompletný deploy + CORS (ISS-098):** [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md)

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 13
- [Modules/Demo/README.md](../backend/app/Modules/Demo/README.md)
- [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md)
- [deploy/DEPLOY.md](deploy/DEPLOY.md) — prod vs demo isolation

## Next

→ **[Iteration 63](ITERATION_63.md)** — Admin system update (prod deploy from UI)  
→ [Iteration 14](ITERATION_14.md) – Code policy engine
