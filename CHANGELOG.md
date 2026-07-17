# Changelog

All notable changes to PaginiumCMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [2.0.11] – 2026-07-17

### Iteration 23 – SEO meta engine

#### Backend
- Add `SeoMetaBuilder` — title template, description, canonical, OG, Twitter Card, JSON-LD
- Add settings group `seo` (`titleTemplate`, `defaultDescription`, `defaultImage`, `robotsDefault`, `twitterCard`)
- Add `GET /api/seo/{type}/{slug}` — public meta for published pages/articles

#### Frontend
- Add `useSeoMeta` hook — fetches SEO API and applies tags to `<head>`
- Wire SEO in `PublicSiteLayout` for home, slug pages, and blog articles

#### Tests
- `SeoMetaBuilderTest`, `SeoControllerTest`, `useSeoMeta.test.ts`

---

## [2.0.10] – 2026-07-17

### Iteration 22 – Ops finish & public discoverability

#### Backend
- Trash admin UI endpoints (unchanged from It. 20)
- Brute-force login lockout — `LoginAttemptTracker`, HTTP 429
- RSS `GET /feed.xml` and sitemap `GET /sitemap.xml`
- Settings groups `feeds` and `security`

#### Frontend
- Trash manager at `/trash`
- RSS `<link rel="alternate">` in public layout
- Login modal 429 lockout message
- Production deploy guard (`build:prod`, `verify-dist-api-url.mjs`)

#### Tests
- `TrashManager.test.tsx`, `FeedGeneratorTest`, `FeedControllerTest`, lockout test

---

## [2.0.9] – 2026-07-17

### Iteration 21 – API contract, automated testing & FE parity

#### Backend
- Extend `JsonResponder` with `validation()`, `conflict()`, `respond()` (legacy/auth flat payloads)
- Migrate HTTP controllers to injected `JsonResponder` (remove duplicate private JSON helpers)
- `GET /api/health` uses standard `{ success, data }` envelope (version 2.0.9)

#### Frontend
- Add MSW handlers (`frontend/src/mocks/`) – enable dev mocks via `VITE_MSW=true`
- Add typed `content.ts` and `user.ts` API clients; fix `api/index.ts` barrel exports
- Vitest MSW contract tests (`npm run test:msw`)

#### Tests & tooling
- `JsonResponderTest` (6 tests)
- `ApiResponseShapeTest` – HTTP contract assertions for success/error/pagination/auth
- Postman smoke collection: `docs/api/PaginiumCMS.postman_collection.json`

#### Completed in 2.0.9 final
- Migrate `BackupController`, `VersionController`, `AuditTrailController` to `JsonResponder`
- Standardize backup list/create to `{ success, data }` envelope
- React Hook Form + Zod on `SettingsView`; `mapApiErrors` for 422
- Newman CI: `.github/workflows/ci.yml`, `scripts/run-api-smoke.sh`
- Refresh `docs/architecture/API.md`
- OpenAPI 3.1 YAML export (optional)
- Full `useApi` → typed client migration (Iteration 17)

---

## [2.0.8] – 2026-07-16

### Iteration 20 – Core hardening & production readiness

#### Backend
- Add `PermissionMiddleware` – RBAC on content write (`content:create|edit|delete`) and media (`media:upload|delete`)
- `AuthorizationManager`: `content:manage` / `media:manage` alias covers domain permissions (ADMIN role)
- Add `MaintenanceModeMiddleware` – enforces `general.maintenanceMode` (503 on public API; admin/editor session exempt)
- Add `GET /storage/{path}` via `StorageController` – serves files from `backend/storage/`
- Setting `general.allowRegistration` – blocks `POST /api/auth/register` when disabled
- Enforce `comments.allowGuestComments` in `CommentsController::submit()`
#### Bug fixes (2.0.8)
- `LogWriter` uses direct filesystem I/O (logs no longer routed through content `FileValidator`)
- Content `DELETE` uses soft-delete to trash instead of permanent removal
- Audit trail skips lookup of version 0; delete versioning is best-effort
- `EnhancedVersionManager::getVersion()` handles invalid JSON safely
- Trash: sidecar `.meta.json` on soft-delete; `GET /api/admin/trash`, `POST /api/admin/trash/{id}/restore`
- Backup cron: `bin/console backup:run-schedule`, `BackupScheduler`, `BackupManager::runScheduledBackupIfDue()`

#### Frontend
- Route `/preview/:slug` (auth + staff roles) for unpublished page preview
- `AdminRoleGuard` on admin shell – USER role redirected to public site
- `document.title` on public site from page/article title + site name
- `VersionHistory` mounted in `MarkdownEditor`
- `DeveloperLogsViewer` at `/developer/logs`
- Vite dev proxy for `/storage`

#### Tests
- `CoreHardeningTest` – RBAC 403, maintenance 503, registration toggle, storage route
- `TrashServiceTest`, `TrashControllerTest`, middleware unit tests (Permission, Maintenance)
- `StorageControllerTest`, `BackupSchedulerTest`, guest comments toggle
- **488 PHPUnit** total, PHPStan L8

#### Docs
- `docs/architecture/CORE_HARDENING.md`
- `docs/ROADMAP.md` – It. 19 ✅, It. 20 🟡

---

## [2.0.7] – 2026-07-16

### Iteration 19 – FlatFile index, pagination & search API

#### Backend
- Add `ContentIndexService` with flat-file index at `data/index/content.json` (flock-safe rebuild/upsert)
- Add `JsonResponder`, `PaginationQuery`, `PaginationMeta` for unified paginated API responses
- Paginated list endpoints: `GET /api/pages|articles?page=&per_page=&search=&status=` returns `{ data, meta }`
- Legacy mode preserved: requests without `page`/`per_page` return full array (no `meta`) for backward compatibility
- Add `GET /api/search?q=` fulltext search over index (published content only, min 2 chars)
- Add dual content storage drivers: `MarkdownContentStorage` (default) + `JsonContentStorage`
- New setting `content.storageFormat` (`md` | `json`) in `SettingsSchema`
- Public API enforces `published` filter when unauthenticated; admins with session see all statuses
- `ContentRepository` index hooks on save/delete; supports `.md` and `.json` files on read

#### Frontend
- `PagesManager`: server-side pagination, search, and status filter via query params
- `SiteSearchModal`: uses `/api/search` with client-side fallback
- Add `api/search.ts`, `PaginationMeta` type on `ApiResponse`

#### Tests
- `ContentRepositoryTest` (index rebuild, pagination, search)
- `ContentControllerTest` (pagination meta, published filter, search)
- Fix `SettingsRepositoryTest` for new `storageFormat` field

---

## [2.0.6] – 2026-07-16

### PHPStan level 8 – full backend compliance

#### Backend
- Resolve all PHPStan level 8 errors across `backend/app`, `backend/bootstrap`, `backend/tests`, and `backend/bin` (0 errors)
- Add `JsonHelper` / `FileHelper` for safe JSON and file I/O
- Add `RouteBootstrap::container()` for nullable Slim container in routes
- Extend `phpstan.neon` with `phpstan-phpunit`, full test/bin paths; remove legacy `backend/routes/web.php`
- Remove unused `ValidationTrait`; fix nullable params and array PHPDoc types project-wide
- Add `LocaleMiddleware` – wires `Lang` to `general.language` settings + `Accept-Language` (sk/en)
- Extend `Lang` with plugin path support (`Lang::addPath`) and SK fallback; fix translator formatting
- PHP 8.5 fixes: guard deprecated `iconv_set_encoding` and `session.sid_*` ini settings in bootstrap
- `GeoIPService` silent HTTP fetch (no suppressed warnings); `TOTPGenerator` uses `InternalClock` (OTPHP 11.3+)
- Add `SecurityMiddlewareTest`, `SecurityHeadersTest`, `LangTest`, `LocaleMiddlewareTest`

#### Frontend
- Switch Vitest from **jsdom** to **happy-dom** (~4× faster test runs)
- Optimize slow component tests (`fireEvent.change`, remove artificial `setTimeout`)
- Add security tests: `client.security.test.ts`, `safeUrl` helper + tests, `test:security` / `audit:security` scripts
- Add i18n foundation: `src/i18n/` (core sk/en), `I18nProvider`, `useI18n()` hook (module blocks via `registerModuleMessages`)

#### Tests & tooling
- PHPUnit: 0 warnings, 0 deprecations (`failOnWarning`, `failOnDeprecation` in `phpunit.xml`)
- Vitest: 73 tests, strict warning/deprecation guards in config
- Remove PHP 8.5 deprecations: `setAccessible()`, `finfo_close()`, `curl_close()`
- Guard session `ini_set()` when session already active (fixes PHPUnit warnings in CLI)
- Upgrade `bacon/bacon-qr-code` to v3.1; add `phpstan/phpstan-phpunit` ^2.0
- Add `scripts/fix-phpstan-iterables.py` for batch array PHPDoc patching

---

## [2.0.5] – 2026-07-15

### Iteration 9 – Prototype backend port + FE wiring

#### Backend
- Port Navigation API (`GET /api/navigation`, `PUT /api/admin/navigation`)
- Port Comments module (public submit/list + admin moderation)
- Port Contact form (`POST /api/contact`) and admin Messages inbox
- Expose GitHub sync admin routes via existing `GitHubService`
- Add `comments` settings group to `SettingsSchema`

#### Frontend
- Add `api/navigation.ts`, `comments.ts`, `contact.ts`, `messages.ts`, `github.ts`
- Add admin views: NavigationManager, CommentsManager, MessagesViewer, GitHubSyncPanel
- Wire ContactForm, ArticleComments, PublicSiteContext navigation
- Fix API base URL for same-origin LAN deploy (`utils/apiBaseUrl.ts`)
- Complete It.8: WYSIWYG toggle, MediaPickerModal, TipTap build fix

#### Tests & docs
- Add repository/controller tests for Navigation, Comments, Messages, GitHub
- Add `docs/ITERATION_9.md`, `docs/deploy/NGINX_API.md`

---

## [2.0.4] – 2026-07-15

### Iteration 8 – Media Manager (Frontend)

#### Frontend
- Add `api/media.ts` – list, upload, patch alt text, delete
- Add `MediaManager` – drag & drop upload, grid previews, alt edit, copy URL, delete
- Wire `/media` admin route; remove `MediaPlaceholder` stub

#### Tests & docs
- Add `MediaRepositoryTest`, `MediaControllerTest` (PHPUnit)
- Add `media.test.ts`, `MediaManager.test.tsx` (Vitest)
- Add `docs/ITERATION_8.md`

---

## [2.0.3] – 2026-07-15

### Iteration 14 – Code policy & Code Editor foundation

#### Backend
- Add `CodePolicyEngine`, `SecurityScanner`, and `CodePolicyViolationException` (422 with grouped errors)
- Fix `CodeEditorManager` project-root resolution, path guards, and `FileInfo[]` listing
- Add `codePolicy` settings group; wire policy stack in DI
- Create `backend/app/Http/Extensions/` and `backend/resources/views/themes/`
- Fix `SimpleLogger` infinite recursion in PSR `log()` method

#### Frontend
- Add `DeveloperUnlockGate` and `api/developer.ts` for gated Code Editor access
- Show policy violation messages on save in `CodeEditor.tsx`

#### Tests & docs
- Add `CodePolicyEngineTest`, `CodeEditorManagerTest`
- Add `docs/ITERATION_14.md`

---

## [2.0.2] – 2026-07-15

### Iteration 7 – Admin dashboard, monitoring, API tracker

#### Backend
- Add `RealtimeTracker` and `DashboardController` (`GET /api/admin/dashboard/overview`)
- Wire Health module to DI and add `health.php` admin routes
- Add `GET /api/admin/analytics/realtime`
- Normalize `HealthController` responses to `{ success, data }` format

#### Frontend
- Rebuild `DashboardView` with health, locks, conflicts, analytics chart, and realtime stats
- Add dashboard panels: `AnalyticsChart`, `LocksPanel`, `ConflictsPanel`, `HealthPanel`
- Add `api/dashboard.ts`; fix `api/health.ts` response handling

#### Tests & docs
- Add `RealtimeTrackerTest`, `DashboardControllerTest`, `AnalyticsChart.test.tsx`
- Add `docs/ITERATION_7.md`

---

## [2.0.1] – 2026-07-15

### Iteration 6 – Notifications, analytics, auth UI

#### Backend
- Extend `SettingsSchema` with `smtp`, `notifications`, `connectors`, and `monitoring` groups
- Add `SmtpTransport`, `NotificationFactory`, `IncidentNotifier`, and channel adapters (email, ntfy, Discord, Telegram, webhook)
- Add `NotificationController` (`GET /api/admin/notifications/overview`, `POST /api/admin/notifications/test`)
- Implement `Reporter`, `AnalyticsManager`, `AnalyticsMiddleware`, and `AnalyticsController`
- Wire notification and analytics services in DI (`services.php`, `bootstrap/app.php`)
- Password reset sends email when SMTP is configured; demo token only in `development`/`testing` without SMTP
- Failed login and audit security events trigger `IncidentNotifier`
- Mask password fields in admin settings API responses; ignore masked values on save
- Expose toast settings via `GET /api/settings/public`

#### Frontend
- Add `NotificationsOverview` at `/notifications` with connector status and visit stats
- Add `api/notifications.ts`, `api/analytics.ts`
- Toast UI driven by settings (enabled, position, duration, debug mode)
- Auth UI: `RegisterModal`, `ForgotPasswordModal`, `ResetPasswordModal`, `ChangePasswordModal`
- `SettingsView` supports `password` field type

#### Tests & docs
- Add `NotificationFactoryTest`, `IncidentNotifierTest`, `notificationSettings.test.ts`
- Rewrite `docs/architecture/SETTINGS.md` in English; add `docs/ITERATION_6.md`
- `.cursorrules`: documentation and commit messages must be in English

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
