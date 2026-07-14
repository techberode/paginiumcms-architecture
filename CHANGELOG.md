# Changelog

All notable changes to PaginiumCMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [2.0.0] – 2026-07-14

**Commit:** [`09b74ab`](https://github.com/techberode/paginiumcms-architecture/commit/09b74ab)  
**Branch:** `main` (fast-forward from `main_local`)

Deliver the PaginiumCMS flat-file core across five planned iterations:
content locking, revisions/drafts/conflicts, settings and shared validation,
and hardened authentication with admin user management and mandatory 2FA on
admin routes. Includes full-stack wiring (Slim 4 API + React SPA), test
infrastructure, documentation, and `.gitignore` cleanup for runtime artifacts.

### Iteration 1 – Content locking

- Add `Core/Locking` (`ContentLock`, `LockManager` over `data/locks.json` with `flock`)
- Add `LockController` and `locking.php` routes (acquire, heartbeat, release)
- Add admin endpoints: `GET /api/locks`, `DELETE /api/locks/{resourceId}`
- Frontend: `api/locks.ts`, `useContentLock` hook, `LockIndicator` component

### Iteration 2 – Auto-save, revisions, conflict detection

- Add `ContentRevision`, `ContentConflictException`, `DraftManager` (`data/drafts/`)
- Extend `ContentController` with revision/baseRevision checks (HTTP 409)
- Add `DraftController`, `drafts.php`, `ContentVersioningService`
- Fix `VersionManager::hydrate()`
- Frontend: `useAutoSave` (60s interval from settings), `api/drafts.ts`, `api/versions.ts`, `DiffViewer`, `MarkdownEditor` integration

### Iteration 3 – 3-way merge and conflict resolution

- Add `Core/Conflict` (`ConflictLogger` over `data/conflicts.json`)
- Add `ConflictController` and `conflicts.php` (`GET/DELETE /api/admin/conflicts`)
- Frontend: `merge3.ts` utility, `ConflictResolver` component, `api/conflicts.ts`
- Auto-merge and manual resolution flow in `MarkdownEditor`

### Iteration 4 – Settings engine, error handler, shared validation

- Add `Core/Settings` (`SettingsSchema`, `SettingsRepository` over `data/settings.json`)
- Add `Core/Validation` (`Validator`, `ValidationRules`, `ValidationException`)
- Add `SettingsController`, `ValidationController`, `ApiErrorHandler` middleware
- Unify HTTP 404/422 error responses across the API
- Frontend: `SettingsView`, `SettingsContext`, `useSettings`, `utils/validation.ts`
- API: `/api/admin/settings/*`, `/api/settings/public`, `/api/validation/rules`

### Iteration 5 – Users, 2FA enforcement, auth hardening

- Implement `UserController` (CRUD + role assignment) and `users.php` routes
- Enforce `TwoFactorMiddleware` on all `/api/admin/*` route groups
- Extend `TwoFactorInterface` with `isTotpVerified()` and `isTwoFactorPassed()`
- Session-based HttpOnly cookie auth; remove dead Bearer token interceptor
- Fix `GET /api/auth/me` to return `{ success, user }`
- Frontend: `UsersManager`, `TwoFactorSettings`, two-step `LoginModal`, `api/users.ts`
- Add `loginAsAdminUser()` helper for integration tests

### Infrastructure and modules

- `bootstrap/app.php`: route auto-discovery, HTTP DI bindings, CORS, auth groups
- Remove dead `bootstrap/routing.php`; wire `Http/Config/services.php`
- Cache: `ChainedDriver` (Memory→File), `ContentCacheService`, `MemoryDriver`
- Developer Mode: `DeveloperModeGate`, `DevTokenGenerator`/`Registry`, `GatedCodeEditorController`, `DeveloperController`
- Media module: `MediaRepository`, `MediaController`, `media.php`
- Replace bootstrap mock content routes with real `ContentController`/`MediaController`
- Register `PasswordPolicyInterface` and fix missing DI imports in HTTP services

### Documentation

- Add `docs/CONTINUATION.md`, `ROADMAP.md`, `architecture/API.md`, `PLUGINS.md`, `SETTINGS.md`, `VERSIONING.md`, `STORAGE.md`
- Add `developer/CODING_STANDARDS.md` (CodePolicyEngine prep for iteration 14)
- Document architectural laws: external plugins outside Core, API↔FE mapping

### Tests

- Backend: PHPUnit 375 tests passing, PHPStan level 8 clean
- Frontend: Vitest 26 tests (`merge3`, `ConflictResolver`, `validation`)
- New test suites: Locking, Drafts, Conflict, Settings, Validation, ContentRevision, UserController, ApiErrorHandler

### Repository hygiene

- Expand `.gitignore` for runtime/test artifacts: backup zips, cache files, PHPUnit user JSON, repomix exports, phpstan temp reports, screenshots
- Remove previously tracked backup archives and generated dumps from VCS

### Post-merge fixes (same release)

| Commit     | Description                                      |
|------------|--------------------------------------------------|
| `12ea642`  | Clean up `UserRepository` merge artifacts        |
| `138b2e3`  | Merge `origin/main` into `main_local`            |
| `b79b82d`  | Remove generated phpstan reports from VCS        |
| `3b0a4d3`  | Restore `TwoFactorInterface` after merge         |

---

## [1.0.0] – Initial structure

- Initial repository layout (`45ea25c`, `57c28cc`)
