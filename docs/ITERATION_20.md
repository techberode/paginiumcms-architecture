# Iteration 20 — Analytika, dashboard disk, robots indexing

**Version:** 2.0.46  
**Status:** ✅ Done  
**Date:** 2026-07-21

## Ciele

1. Samostatná admin stránka **Analytika** (KPI karty, taby: Prehľad / Stránky / Zdroje / Zariadenia / Geografia)
2. Rozšírenie **Dashboardu** — rýchle odkazy + disková štruktúra s veľkosťou obsahu
3. **SEO:** zapínanie/vypínanie indexovania (`robots.txt` + meta tagy)

## Backend

| Súbor | Zmena |
|-------|--------|
| `Tracker.php` | Visit záznamy obohatené o `deviceType`, `browser`, `country`, `city` |
| `Reporter.php` | Periódy 7/14/30 dní, `getBrowserStats()`, lepší `unique_visitors` |
| `AnalyticsController.php` | API vracia `geo`, `browsers`, `top_articles` |
| `ContentStorageStatsService.php` | Veľkosť flat-file obsahu pre dashboard |
| `DashboardController.php` | `storage.content` blok |
| `SettingsSchema.php` | `seo.allowSearchIndexing` |
| `RobotsTxtGenerator.php` | `Disallow: /` pri vypnutom indexovaní |
| `SeoMetaBuilder.php` | Globálny `noindex` pri vypnutom indexovaní |

## Frontend

| Súbor | Zmena |
|-------|--------|
| `AnalyticsView.tsx` | Nová stránka `/analytics` |
| `DashboardView.tsx` | Rýchle odkazy, disk panel, link na analytiku |
| `DashboardDiskStructurePanel.tsx` | UI podľa mockupu |
| `AdminSidebar.tsx` | Položka Analytika pod Prehľadom |
| `LoginBackgroundImagePicker.tsx` | Upload pozadia prihlásenia (It.19 doplnok) |
| i18n moduly `analytics`, rozšírené `dashboard`, `settings` |

## Overenie

- [ ] `/analytics` — KPI, graf, taby Stránky/Zdroje/Zariadenia/Geografia
- [ ] `/dashboard` — 4 rýchle odkazy + disková štruktúra s KB/MB
- [ ] Nastavenia → SEO → vypnúť indexovanie → `/robots.txt` = `Disallow: /`
- [ ] Verejný web meta `noindex` (okrem stránok s vlastným noIndex)

## Súvisiace

- [ITERATION_19.md](ITERATION_19.md) — audit activity + login background
- [ISSUES.md](ISSUES.md) — ISS-046 … ISS-050
- [developer/RELEASE.md](developer/RELEASE.md) — C&P 2.0.46
