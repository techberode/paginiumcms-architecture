# Iteration 18 – Admin UI Localization (i18n)

**Status:** Partial  
**Version:** 2.0.6+ (foundation); full UI migration pending

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
| `src/i18n/core/{sk,en}.ts` | Core admin catalog |
| `registerModuleMessages()` | Per-module blocks under `src/i18n/modules/` |
| `I18nProvider` + `useI18n().t()` | Locale from `SettingsContext` |
| `src/i18n/index.test.ts` | Core catalog tests |

## Remaining ⏳

- Migrate all admin components from hardcoded strings → `useI18n()`
- FE module files: media, navigation, users, settings, dashboard, …
- Plugin i18n: `Http/Extensions/{id}/lang/` + `frontend/src/extensions/{id}/i18n/`
- Public site (`PublicSite`) – separate or shared catalog
- Optional: `GET /api/i18n/{locale}` for dynamic loading

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

→ [Iteration 19](ITERATION_19.md) – FlatFile index & pagination
