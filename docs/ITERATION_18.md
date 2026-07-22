# Iteration 18 – Admin UI Localization (i18n)

**Status:** ✅ Shipped · It.18a–d **2.0.44** · It.18e **2.0.46** · **It.18f ✅ Beta gate (2.0.47)**  
**Version:** 2.0.44 · 2.0.46 · **2.0.47** (It.18f + CI hotfix ISS-059)

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

### It.18f ✅ (Beta gate, 2.0.47)

| Path | Role |
|------|------|
| `src/i18n/modules/comments/{sk,en}.ts` | **CommentsManager** — inbox, bulk, OTP |
| `src/i18n/modules/messages/{sk,en}.ts` | **MessagesViewer** — priority, bulk, status |
| `src/i18n/modules/backups/{sk,en}.ts` | **BackupManager** — create/import/restore |
| `src/i18n/modules/trash/{sk,en}.ts` | **TrashManager** — restore, purge, empty |
| `src/i18n/modules/logs/{sk,en}.ts` | **LogsManager** — severity, source, purge |
| `src/i18n/modules/platform/{sk,en}.ts` | Firewall, scheduler, extensions, demo, ACL, GitHub, notifications, security audit, blueprint, command palette |
| `src/i18n/modules/editor/{sk,en}.ts` | Content editor shell, Markdown/WYSIWYG, SEO, tags, site preview, media modals |
| `CommentsManager.tsx`, `MessagesViewer.tsx`, `BackupManager.tsx`, `TrashManager.tsx`, `LogsManager.tsx` | Ops moduly → `useI18n()` |
| ~35 ďalších komponentov | Platform + editor + dashboard panely → `useI18n()` |
| `src/i18n/modules/ops18f/ops18f.test.ts` | Catalog parity (comments, messages, backups, trash, logs) |
| `src/i18n/modules/platform/platform.test.ts` | Platform catalog smoke |
| `src/i18n/modules/editor/editor.test.ts` | Editor catalog smoke |

**ISS-060 (fixed in 2.0.47):** `settings/en.ts` — sekcia `workflows` mala SK copy-paste; EN admin zobrazoval slovenské OTP labely.

### Test harness (ISS-059, CI hotfix)

| Path | Role |
|------|------|
| `src/test/renderWithProviders.tsx` | `TestI18nProvider` wrapper (default locale `sk`, optional `{ locale: 'en' }`) |
| `src/test/renderWithRouter.tsx` | Delegates to `renderWithProviders` + `MemoryRouter` |
| `MediaPreviewLightbox.test.tsx`, `SitePreviewModal.test.tsx`, `editorToolbar.test.tsx` | Switched from raw `render()` |
| `HealthPanel.test.tsx`, `LocksPanel.test.tsx` | Provider + SK assertion strings |
| `MediaManager.test.tsx` | Dialog queries use SK labels (`Upraviť metadáta`, `Titulok`, `Uložiť zmeny`) |

**CI failure @ `f0a885c`:** `useI18n must be used within I18nProvider` — 6 Vitest suites. Detail: [ISSUES.md](ISSUES.md) ISS-059.

## Remaining ⏳ (post-Beta)
- Public site (`PublicSite`) – separate catalog
- Auth modals / register shell – outside admin i18n scope today
- `formatAuditEvent.ts` — SK messages regardless of admin locale (backend formatter)
- Plugin i18n: `Http/Extensions/{id}/lang/` + `frontend/src/extensions/{id}/i18n/`
- Optional: `GET /api/i18n/{locale}` for dynamic loading
- Settings `general.language` enum from `SupportedLocalesRegistry`

## i18n law

1. One file per module and language
2. Core has a core catalog; plugins never overwrite core keys
3. Ideal timing: after It. 19–21 text stabilization

## Tests

- `i18n/index.test.ts` (Vitest)
- Module catalog tests under `src/i18n/modules/*/`
- `LocaleMiddlewareTest` (PHPUnit)
- Vitest **210/210** after ISS-059 fix

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 18
- [ISSUES.md](ISSUES.md) – ISS-059, ISS-060

## Next

→ [Iteration 19](ITERATION_19.md) · Beta infra checklist ([CONTINUATION.md](CONTINUATION.md))
