# Demo modul (Iterácia 13 v3 + v4)

**Len pre hosting `demo.paginiumcms.com` — nie pre zákaznícku produkciu.**

Predstav si to ako **predvádzacie auto v showroome**: nie je na predaj, ale môžeš si na ňom odviezť celú trasu (admin, články, verejný web). Po skúške sa vráti do výchozieho stavu — ďalší návštevník dostane čistú jazdu.

Izolované úložisko na to, aby návštevník mohol **naplno vyskúšať CMS** bez zásahu do reálnych dát. Zákaznícke inštalácie PaginiumCMS tento modul **nepoužívajú** (`DEMO_MODE=false`).

**Release:** `v2.1.0-beta.11` (v4) · `v2.1.0-beta.10` (v3) · [ITERATION_13.md](../../../docs/ITERATION_13.md)

## Kde beží

| Prostredie | `DEMO_MODE` |
|------------|-------------|
| `paginiumcms.com` (marketing + docs) | `false` |
| `demo.paginiumcms.com` (try CMS) | `true` |
| Zákaznícka produkcia | `false` |

## v3 — čo je v ukážkovom snapshote

Po resete (`/demo` → Reset demo seed) demo strom obsahuje:

| Modul | Ukážkové dáta |
|-------|----------------|
| Stránky | home, about, **contact** (formulár) |
| Blog | 2 články s komentármi |
| Komentáre | 2 schválené v `data/comments.json` |
| Správy | 1 kontaktná správa v `data/messages/` |
| Newsletter | 1 odberateľ + footer newsletter zapnutý |
| Nastavenia | appearance, login info, company na kontakte |

## Aktivácia (iba demo inštancia)

1. Do `.env` (koreň projektu **alebo** `backend/.env`):

```bash
DEMO_MODE=true
APP_URL=https://demo.paginiumcms.com
DEMO_PUBLIC_URL=https://demo.paginiumcms.com
VITE_PUBLIC_URL=https://demo.paginiumcms.com
DEMO_AUTO_RESET_MINUTES=60
# DEMO_STORAGE_QUOTA_BYTES=2147483648  # synthetic dashboard quota (default 2 GiB; never exposes host disk)
SESSION_LIFETIME=14400
```

2. **Reštartuj PHP** (Docker / `php -S`).

3. Over: `/demo` → `DEMO_MODE: zapnutý`, banner v admin shell, verejný amber pás.

4. Po upgrade na **beta.10+**: `/demo` → **Reset demo seed** (ADMIN+).

**Login v prehliadači nefunguje, curl áno?** → ISS-098 (CORS / `APP_URL`). Postup: [`docs/deploy/DEMO_DEPLOY.md`](../../../docs/deploy/DEMO_DEPLOY.md).

**Nginx (host):** [`docs/deploy/nginx-demo.paginiumcms.com.conf`](../../../docs/deploy/nginx-demo.paginiumcms.com.conf) — upstream port = stack `BACKEND_PORT` (napr. `8091`), root `/var/www/paginiumcms-demo/frontend/dist`.

## API (v3 + v4)

| Route | Účel |
|-------|------|
| `GET /api/demo/public-info` | Countdown resetu + `loginEmail` + `credentials` (iba pri `DEMO_MODE=true`) |
| `POST /api/demo/quick-login` | One-click demo admin session (S-DEMOCREDS, v4) |
| `GET /api/admin/demo/status` | Admin stav + schedule (bez credentials) |
| `POST /api/admin/demo/reset` | ADMIN+ — re-seed snapshot |

## Úložisko (v2/v3)

| Cesta | Účel |
|-------|------|
| `storage/app/demo/` | **Celý CMS** pri `DEMO_MODE=true` (obsah, users, settings) — cesta v repozitári: `backend/storage/app/demo/` |
| `backend/storage/app/content/` | Produkčný obsah — na demo inštancii sa **nepoužíva** |

`DemoStorageService::assertIsolatedFromProduction()` — demo cesta nesmie prekrývať content.

## Cron auto-reset

```cron
*/15 * * * * cd /var/www/paginiumcms-demo && /usr/bin/php backend/bin/console demo:reset-if-due >> /var/log/paginium-demo-reset.log 2>&1
```

**Pred nastavením cronu** over manuálne: `php backend/bin/console demo:reset-if-due`.

Ak `Permission denied` na `data/plugins.json` → host user vs Docker `www-data`. Fix: `chown user:www-data`, dirs `2775` (ISS-099, rovnaký pattern ako It.62). Postup: [`docs/deploy/DEMO_DEPLOY.md`](../../../docs/deploy/DEMO_DEPLOY.md) § ISS-099.

**Celé API HTTP 500 (health/login)?** → chýba alebo nie je zapisovateľný `backend/storage/app/demo/data/` (ISS-102). Postup: [`DEMO_DEPLOY.md`](../../../docs/deploy/DEMO_DEPLOY.md) § First-run storage bootstrap.

## Demo účet

| E-mail | Heslo |
|--------|-------|
| `demo@paginiumcms.com` | `Demo123!` |

Len tento účet funguje na demo inštancii (`DemoLoginGuard`). **Heslo nie je v public API** (v4) — prihlásenie cez login stránku **Prihlásiť ako demo admin** alebo manuálne.

**Admin UX (v4):** Sidebar položka „Demo modul“ je na demo inštancii skrytá — správa cez amber banner → `/demo`.

## Prod marketing (bez demo modulu)

**Nastavenia → Marketing** na `paginiumcms.com`: footer odkaz na demo URL (`demoFooterLinkEnabled`, `demoUrl`).

Detail: [ITERATION_13.md](../../../docs/ITERATION_13.md) · [DEMO_DEPLOY.md](../../../docs/deploy/DEMO_DEPLOY.md)
