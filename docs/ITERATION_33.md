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

## Acceptance criteria

- [x] Sources tab zobrazuje typ a label zdroja (nie len raw URL)
- [x] Geo tab zobrazuje vlajku, mesto a maskované IP
- [x] PHPUnit: RefererAnalyzer, IpMasker, Reporter enrichment
- [x] Vitest: `countryFlag.test.ts`

## Smoke test

1. Otvor verejnú stránku v anonymnom okne (generuje visit s referer/direct).
2. Admin → **Analytika** → tab **Zdroje** — over direct / search / social label.
3. Tab **Geografia** — vlajka krajiny, maskovaná IP v „Posledné návštevy“.
4. API: `GET /api/admin/analytics/overview?period=7` — polia `top_referers[].type`, `geo[].countryCode`, `geo_visits[]`.

## CI hotfix (súvisiaci)

| Problém | Oprava |
|---------|--------|
| `ExtensionManifestValidator.php` parse error | chýbajúca `}` na konci triedy |
| PHPUnit mock `EditorComponentRegistry` (final) | mock `PluginManagerInterface` + reálny registry |
| Vitest `ContactForm.test.tsx` | `importOriginal` pre `TestSettingsProvider` |

## Súvisiace

- [ITERATION_6.md](ITERATION_6.md) — analytics MVP
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) §33
