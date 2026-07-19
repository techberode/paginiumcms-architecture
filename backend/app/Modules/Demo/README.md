# Demo modul (Iterácia 13)

**Len pre hosting `demo.paginiumcms.com` — nie pre zákaznícku produkciu.**

Predstav si to ako **predvádzacie auto v showroome**: nie je na predaj, ale môžeš si na ňom odviezť celú trasu (admin, články, verejný web). Po skúške sa vráti do výchozieho stavu — ďalší návštevník dostane čistú jazdu.

Izolované úložisko na to, aby návštevník mohol **naplno vyskúšať CMS** bez zásahu do reálnych dát. Zákaznícke inštalácie PaginiumCMS tento modul **nepoužívajú** (`DEMO_MODE=false`).

## Kde beží

| Prostredie | `DEMO_MODE` |
|------------|-------------|
| `paginiumcms.com` (marketing + docs) | `false` |
| `demo.paginiumcms.com` (try CMS) | `true` |
| Zákaznícka produkcia | `false` |

## Aktivácia (iba demo inštancia)

1. Do `.env` (koreň projektu **alebo** `backend/.env`):

```bash
DEMO_MODE=true
APP_URL=https://demo.paginiumcms.com
VITE_PUBLIC_URL=https://demo.paginiumcms.com
```

2. **Reštartuj PHP** (Docker / `php -S`).

3. Over: `/demo` → `DEMO_MODE: zapnutý`, banner v admin shell.

4. Seed (SUPER_ADMIN): `/demo` → **Reset demo seed**.

## Úložisko (v2)

| Cesta | Účel |
|-------|------|
| `storage/app/demo/` | **Celý CMS** pri `DEMO_MODE=true` (obsah, users, settings) |
| `storage/app/content/` | Produkčný obsah — na demo inštancii sa **nepoužíva** |

## Cron auto-reset

```bash
*/15 * * * * cd /path/to/project && php backend/bin/console demo:reset-if-due
```

Env: `DEMO_AUTO_RESET_MINUTES=60`, `SESSION_LIFETIME=14400`

## Demo účet

| E-mail | Heslo |
|--------|-------|
| `demo@paginiumcms.com` | `Demo123!` |

Detail: [ITERATION_13.md](../../../docs/ITERATION_13.md) · Release **2.0.28**
