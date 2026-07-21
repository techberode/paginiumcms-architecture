# Iteration 18 – Admin UI Localization (i18n)

**Status:** ✅ Shipped (2.0.44) · It.18a–d complete · **It.18e ✅ (2.0.46)**  
**Version:** 2.0.44 · **2.0.46** (It.18e media/navigation/dashboard)

## Summary

Migrate all admin UI strings from hardcoded Slovak to `useI18n()`, with backend `Lang.php` and modular translation catalogs per feature.

## Done ✅

### Backend

| Path | Role |
|------|------|
| `Support/Lang.php` | Translator, default `sk`, fallback SK, `Lang::addPath()` for plugins |
| `backend/lang/{sk\|en}/*.php` | Modules: content, comments, contact, media, messages, navigation, github |
| `LocaleMiddleware` | Locale from `general.language` + `Accept-Language` |
| `SettingsSchema` | `general.language` (`sk` \| `en`) |

### Frontend

| Path | Role |
|------|------|
| `src/i18n/core/{sk,en}.ts` | Core admin catalog (`common.*`) |
| `src/i18n/modules/admin/{sk,en}.ts` | **It.18a** — sidebar + header (`admin.nav.*`, `admin.header.*`) |
| `src/i18n/modules/list/{sk,en}.ts` | **It.18b** — shared list toolbar, pagination, bulk bar, view modes |
| `src/i18n/modules/content/{sk,en}.ts` | **It.18b** — pages/articles manager (`PagesManager`) |
| `src/i18n/modules/settings/{sk,en}.ts` | **It.18c** — settings UI, groups, field labels, cache panel |
| `src/i18n/modules/settings/helpers.ts` | Translate group/field/enum with API fallback |
| `src/i18n/registerModules.ts` | Registers module catalogs at app boot |
| `registerModuleMessages()` | Per-module blocks under `src/i18n/modules/` |
| `I18nProvider` + `useI18n().t()` | Locale from `SettingsContext` |
| `AdminSidebar.tsx`, `AdminHeader.tsx` | Migrated to `useI18n()` (It.18a) |
| `PagesManager.tsx` + list components | Migrated to `useI18n()` (It.18b) |
| `SettingsView.tsx`, `CacheManagerPanel.tsx` | Migrated to `useI18n()` (It.18c) |
| `src/i18n/modules/translations/{sk,en}.ts` | **It.18d** — translation editor UI |
| `TranslationEditor.tsx` + `/translations` route | Light Monaco editor for lang files (It.18d) |
| `TranslationFileManager` + `/api/admin/translations/*` | Backend/FE catalog read/write, Admin+2FA only |
| Module tests under `src/i18n/modules/*/` | Catalog parity + translate smoke tests |

### It.18e ✅ (2.0.46)

| Path | Role |
|------|------|
| `src/i18n/modules/media/{sk,en}.ts` | **MediaManager** — actions, filters, stock, metadata |
| `src/i18n/modules/navigation/{sk,en}.ts` | **NavigationManager** — labels, toasts, form copy |
| `src/i18n/modules/dashboard/{sk,en}.ts` | **DashboardView** — KPI, hero, stats, quick links, disk panel |
| `MediaManager.tsx`, `NavigationManager.tsx`, `DashboardView.tsx` | Migrated to `useI18n()` |
| `NavigationManager.test.tsx`, `MediaManager.test.tsx` | `TestI18nProvider` + SK label assertions |

## Remaining ⏳

- Migrate remaining admin components → `useI18n()` (comments, messages, audit, backups…)
- Plugin i18n: `Http/Extensions/{id}/lang/` + `frontend/src/extensions/{id}/i18n/`
- Public site (`PublicSite`) – separate or shared catalog
- Optional: `GET /api/i18n/{locale}` for dynamic loading
- Settings `general.language` enum from `SupportedLocalesRegistry`

## i18n law

1. One file per module and language
2. Core has a core catalog; plugins never overwrite core keys
3. Ideal timing: after It. 19–21 text stabilization

## Tests

- `i18n/index.test.ts` (Vitest)
- `LocaleMiddlewareTest` (PHPUnit)

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 18

## Next

→ [Iteration 19](ITERATION_19.md) · [Iteration 20](ITERATION_20.md)
