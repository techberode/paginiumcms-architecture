# Iteration 4 – Settings Engine, Error Handler & Shared Validation

**Status:** Complete  
**Version:** 2.0.6 (core foundation)

## Summary

Flat-file settings with schema-driven admin UI, centralized JSON error handling, and shared validation rules between backend and frontend.

## Backend

| Path | Role |
|------|------|
| `Core/Settings/SettingsSchema.php` | Schema groups: `general`, `content`, `editor`, … |
| `Core/Settings/Services/SettingsRepository.php` | `data/settings.json` (stores only deltas), flock |
| `Http/Controllers/Admin/SettingsController.php` | CRUD + `publicSettings` + reset |
| `Http/Routes/settings.php` | `/api/admin/settings/*`, `/api/settings/public` |
| `Core/Validation/Validator.php` | Stateless validator |
| `Core/Validation/ValidationRules.php` | Rule catalog (login, password, content, user) |
| `Http/Controllers/Validation/ValidationController.php` | `GET /api/validation/rules` |
| `Http/Support/ApiErrorHandler.php` | Maps exceptions → `{ success: false, error, errors? }` |

### Response shapes

| Case | HTTP | Shape |
|------|------|-------|
| Validation error | 422 | `{ success: false, error, errors }` |
| Generic error | 4xx/5xx | `{ success: false, error }` |
| Public settings slice | 200 | `{ success: true, data }` |

## Frontend

| File | Role |
|------|------|
| `src/api/settings.ts` | Typed settings API |
| `src/api/validation.ts` | Download shared rules |
| `src/components/backend/SettingsView.tsx` | Schema-driven settings form |
| `src/context/SettingsContext.tsx` | Global settings access |
| `src/hooks/useSettings.ts` | Settings hook |
| `src/utils/validation.ts` | FE mirror of backend Validator |
| `src/hooks/useAutoSave.ts` | Interval from `content.autoSaveInterval` |

## Tests

- `SettingsRepositoryTest`
- `ValidatorTest`
- `ApiErrorHandlerTest`
- `validation.test.ts` (Vitest)

## Related docs

- [SETTINGS.md](architecture/SETTINGS.md)
- [API_CONTRACT.md](architecture/API_CONTRACT.md)

## Next

→ [Iteration 5](ITERATION_5.md) – users admin + auth hardening
