# Iteration 33 – Analytics enrichment

**Status:** ✅ Implemented  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.6 Analytics](ITERATION_6.md) ✅ · [It.52 Dashboard KPI](ITERATION_52.md) ✅

## Cieľ

Rozšíriť admin **Analytiku** o čitateľnejšie zdroje návštevnosti a geografiu — bez novej DB, nad existujúcim flat-file `Core/Analytics/`.

## Rozsah

| Oblasť | Popis |
|--------|--------|
| **Zdroje (Sources)** | Klasifikácia refererov: direct / search / social / referral + ľudský label (Google, Facebook…) |
| **Geografia** | Vlajka krajiny (`countryCode`), top mesto, maskované ukážkové IP |
| **Posledné návštevy** | Zoznam `geo_visits` s maskovanou IP a URI |
| **UI** | Ikony na taboch, emoji vlajky, typ zdroja |

## Technicky

### Backend

- `RefererAnalyzer` — normalizácia HTTP referer → `source`, `domain`, `type`
- `AnalyticsIpMasker` — GDPR-aware maskovanie IPv4/IPv6 pre admin UI
- `Tracker::saveVisit()` — ukladá `countryCode` z `GeoIPService`
- `Reporter::getTopReferers()` — enriched payload
- `Reporter::getGeoStats()` — `countryCode`, `city`, `sample_ips`
- `Reporter::getRecentGeoVisits()` — posledné návštevy pre geo tab
- `GET /api/admin/analytics/overview` — pole `geo_visits`

### Frontend

- `utils/countryFlag.ts` — ISO kód → emoji vlajka
- `AnalyticsView.tsx` — tabs s ikonami, Sources + Geo UI
- `api/analytics.ts` — typy `TopReferer`, `GeoStat`, `GeoVisit`

### SPA pageview beacon (2026-07 fix)

Production nginx serves the React SPA as static files — PHP `AnalyticsMiddleware` never runs on public routes. Fix:

| Layer | Change |
|-------|--------|
| **BE** | `POST /api/analytics/pageview` — public beacon (CSRF-exempt, rate-limited, path-validated) |
| **BE** | `PageviewPathValidator` — allow public SPA paths only (reject `/api/*`, traversal) |
| **BE** | `AnalyticsManager::trackPageViewFromRequest()` — dedupe 3s via `CacheManager` |
| **FE** | `useAnalyticsPageview()` in `PublicSiteLayout` — fires on React Router navigation |
| **FE** | Respects cookie consent (`analytics` category when banner enabled) |
| **FE** | Empty states in dashboard chart + analytics tabs when no data yet |

Localhost tracking: enabled when `APP_ENV` is `development`, `local`, or `testing`, or `ANALYTICS_TRACK_LOCALHOST=true`.

## Acceptance criteria

- [x] Sources tab zobrazuje typ a label zdroja (nie len raw URL)
- [x] Geo tab zobrazuje vlajku, mesto a maskované IP
- [x] PHPUnit: RefererAnalyzer, IpMasker, Reporter enrichment
- [x] Vitest: `countryFlag.test.ts`

## Smoke test

1. Accept analytics cookies on the public site (if cookie banner is enabled).
2. Browse several public pages (`/`, `/blog/...`) — each navigation sends `POST /api/analytics/pageview`.
3. Admin → **Analytika** → tab **Prehľad** — KPI a graf by mali mať nenulové hodnoty (po pár sekundách).
4. Tab **Zdroje** — direct / search / social label.
5. Tab **Geografia** — vlajka krajiny, maskovaná IP v „Posledné návštevy“.
6. Dashboard (`/dashboard`) — analytics chart zobrazí dáta alebo empty state s hintom.
7. API: `GET /api/admin/analytics/overview?period=7` — polia `top_referers[].type`, `geo[].countryCode`, `geo_visits[]`.

## CI hotfix (súvisiaci)

| Problém | Oprava |
|---------|--------|
| `ExtensionManifestValidator.php` parse error | chýbajúca `}` na konci triedy |
| PHPUnit mock `EditorComponentRegistry` (final) | mock `PluginManagerInterface` + reálny registry |
| Vitest `ContactForm.test.tsx` | `importOriginal` pre `TestSettingsProvider` |

## Súvisiace

- [ITERATION_6.md](ITERATION_6.md) — analytics MVP
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) §33
