# Changelog

All notable changes to PaginiumCMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

### Iteration → release index

| Iteration | Release | Changelog |
|-----------|---------|-----------|
| It.6 – Notifications, SMTP, analytics, auth UI | **2.0.1** | [below](#201--2026-07-15) |
| It.10 – RSS/sitemap feeds (via It.22) | **2.0.10** | [below](#2010--2026-07-17) |
| It.7 – Scheduled monitoring reports + log incidents | **2.0.17** | [below](#2017--2026-07-18) |
| It.29 – Cron planner + Job Queue | **2.0.18** | [below](#2018--2026-07-18) |
| Users admin UI refresh | **2.0.19** | [below](#2019--2026-07-18) |
| It.30 – Content editory, cache fix, admin zoznamy | **2.0.20** | [below](#2020--2026-07-18) |
| Code Editor, 2FA UX, developer unlock fixes | **2.0.21** | [below](#2021--2026-07-18) |
| It.16 – Code Editor create/delete/restore | **2.0.22** | [below](#2022--2026-07-18) |
| Content SEO media picker + blog preview fix | **2.0.23** | [below](#2023--2026-07-18) |

---

## [2.0.23] – 2026-07-18

### Content — SEO náhľadový obrázok z médií

#### Frontend
- `SeoMetadataPanel` — **Vybrať z médií**, miniatúra, vymazanie OG obrázka
- `MediaPickerModal` — voliteľný `urlFormat: storage` pre SEO cesty
- `BlogRenderer` — náhľad v blogových kartách cez `contentPreviewImage` (`seoImage` / `ogImage` / `featuredImage`)
- `contentPreviewImage.ts` + unit testy

#### Backend
- `Article::getFeaturedImage()` — fallback na `seoImage` / `ogImage` vo front matter

#### Docs
- [docs/user/CONTENT_EDITOR.md](docs/user/CONTENT_EDITOR.md) — editor podstránok/článkov, SEO, médiá

#### CI (same day)
- PHPStan level 8 — 44 chýb opravených (analytics, settings, monitoring, scheduler typy)
- Reset-password test — token v `APP_ENV=testing` aj pri aktívnom SMTP

---

## [2.0.22] – 2026-07-18

### Code Editor — Iteration 16 (create / delete / restore)

#### Backend
- `CodeEditorManager`: `createFile()`, `deleteFile()`, `restoreBackup()`
- `FileBackup::resolveBackupByBasename()` — bezpečné mapovanie zálohy na cestu
- Routes: `POST /file`, `DELETE /file`, `POST /restore` (gated + policy + syntax)

#### Frontend
- `CodeEditorFileActions` — Nový súbor, Zmazať súbor, obnova zo zálohy
- `codeEditor.ts` — typované API pre create / delete / restore

#### Docs
- [docs/ITERATION_16.md](docs/ITERATION_16.md) — stav hotový (plugin editor čaká na It. 15)
- [docs/user/CODE_EDITOR.md](docs/user/CODE_EDITOR.md) — create/delete/restore sekcie

#### Tests
- `CodeEditorManagerTest::testCreateDeleteAndRestoreFile`
- `CodeEditorControllerTest::testCreateDeleteAndRestoreFileFlow`

---

## [2.0.21] – 2026-07-18

### Code Editor & Developer Mode

#### Backend
- Developer unlock: fresh user from DB after 2FA setup (fixes 403 with stale session)
- `DeveloperModeGate`: `APP_ENV=development` enables feature on LAN without explicit `DEVELOPER_MODE`
- Code editor: `GET /directories`, `listAllAllowedFiles()`, whitelist-only file tree
- Session sync after 2FA enable/verify/disable (`refreshCurrentUserFromStorage`)
- Clearer developer unlock error messages

#### Frontend
- Monaco Code Editor (It.8/16)
- Account security page `/account/security` — 2FA QR setup (Google Authenticator)
- Code Editor: all allowed directories tree, safety banner, Save confirm, **Zamknúť editor** (lock)
- Developer unlock UI: Slovak errors, env hint for LAN backend

#### Docs
- [docs/user/CODE_EDITOR.md](docs/user/CODE_EDITOR.md) — user guide
- [docs/user/DEVELOPER_MODE.md](docs/user/DEVELOPER_MODE.md) — gate & dev tokens
- CLI: `backend/bin/cli-env.php` for dev-token scripts without `.env`

#### Tests
- `DeveloperModeGateTest`, `TwoFactorSettings.test.tsx`, `buildFileTree.test.ts`, code editor controller tests

---

## [2.0.20] – 2026-07-18

### Content admin polish (It.30)

#### Backend
- Fix content list/cache: cache stores serialized arrays, not PHP `Content` objects (fixes empty lists after refresh)
- Do not cache `null` item lookups; ChainedDriver increment uses file layer as source of truth
- Rebuild empty content index when files exist on disk
- CLI `content:cache-purge [--reindex]` for post-deploy cache reset
- Stop spam login alert emails: notify on lockout only (not every failed attempt); skip `@example.com` / test emails and `APP_ENV=testing`; cooldown throttle

#### Frontend
- Markdown + WYSIWYG editors with mode switch, live preview, `contentFormat` in API
- `ContentEditorShell`: prototype layout (path, menu hint, SEO panel, stats footer)
- Shared `AdminListToolbar`; PagesManager SK labels, mobile cards, `itemsPerPage` from settings
- Media library toolbar aligned with AdminListToolbar; article preview links to `/blog/{slug}`

#### Docs
- [ITERATION_30.md](docs/ITERATION_30.md)

---

## [2.0.19] – 2026-07-18

### Admin user management UI refresh

#### Backend
- User fields: `username`, `active`; admin detail exposes `twoFactorSecret`
- Settings `security.requireTwoFactorStaff` — enforce 2FA for EDITOR/ADMIN/SUPER_ADMIN
- Block login for inactive accounts
- `GET /api/admin/users` returns meta; `GET /api/admin/users/{id}` returns enforcement flags

#### Frontend
- `/users` form aligned with admin mockup: username, status, role labels, password toggle, 2FA section with secret display

#### Docs
- [ITERATION_5.md](docs/ITERATION_5.md) — admin form field map

---

## [2.0.18] – 2026-07-18

### Iteration 29 – Cron planner + Job Queue

#### Backend
- Add `Core/Scheduler/*` — flat-file registry, run history, queue, `CronExpressionEvaluator`, handlers for backup + monitoring
- CLI: `scheduler:run`, `worker:process` (legacy `backup:run-schedule` / `monitoring:run-schedule` kept)
- Admin API: `GET/POST/PUT/DELETE /api/admin/jobs`, run + queue process
- Settings group `scheduler` (master switch, retain runs)
- Fix missing `GET/POST /api/admin/backups/schedule` for BackupManager UI

#### Frontend
- `/scheduler` — Plánovač: job list, CRON edit, run now, cron simulation
- `api/jobs.ts`, sidebar entry

#### Tests
- PHPUnit: `CronExpressionEvaluatorTest`, `ScheduledJobRunnerTest`

---

## [2.0.17] – 2026-07-18

### Iteration 7 – Scheduled monitoring reports + log incidents

#### Backend
- Add `Core/Monitoring/*` — `MonitoringReportScheduler`, `MonitoringReportBuilder`, `LogIncidentScanner`, `MonitoringScheduler`, `SchedulerStateStore`
- Add `monitoring:run-schedule` CLI command; wire DI in `bootstrap/app.php`
- Extend `SettingsSchema` monitoring group (report interval/time, connectors, log incident toggles)
- Extend `Reporter` — `getTopIpStats()`, `getTopArticles()`, week aggregation for analytics
- Extend `IncidentNotifier::notifyViaConnectorDetailed()` with preflight reasons (`connector_inactive`, `missing_recipient`, …)
- Extend `LogWriter::readSince()` for incident scanner
- Admin API: `GET /schedule`, `POST /report/send`, `POST /schedule/run`
- Dark HTML monitoring email (Slovak sections: IP stats, top pages/articles/referrers, health, flat-file)

#### Frontend
- `/notifications` — scheduled reports card, manual send, cron simulation, delivery blockers hint
- `api/notifications.ts` — report send + schedule run with error reasons

#### Fixes (same release)
- `bin/console` — Symfony 8 `addCommand()` instead of `add()`
- Notification report API — `message` + `result.reason` on 422
- Settings crash — `zodFromRules` optional `.max()` (ISS-009)
- Debug route 404, phantom users, backup retention (see ISSUES.md)

#### Tests
- PHPUnit: 7 new monitoring tests under `Core/Monitoring/Services/`
- Vitest: 107/107

#### Docs
- [ITERATION_7.md](docs/ITERATION_7.md), ROADMAP It.7 ✅, TESTING.md, DEV.md cron
- Builds on **It.6** (notifications/SMTP/analytics) shipped in [2.0.1](#201--2026-07-15)

---

## [2.0.16] – 2026-07-18

### Iteration 28 – Bulk actions platform

#### Frontend
- Add `useBulkSelection` + `BulkActionBar` — shared multi-select pattern
- **MediaManager** — refactor to shared bulk bar + select-all in list table
- **Pages / Articles** — bulk publish, draft, archive, delete
- **Trash** — bulk restore
- **Comments** — bulk approve, reject, delete
- **Users** — bulk delete with selection guard (not self)
- **Backups** — bulk restore/delete, import ZIP, SHA-256 column, verify + download with hash header

#### Backend
- Add `BulkBatchResult` — `{ processed, succeeded, failed, results[] }` contract
- Content: `POST/PATCH /api/{pages|articles}/bulk-*`
- Trash: `POST /api/admin/trash/bulk-restore`
- Comments: `POST /api/admin/comments/bulk-*`
- Users: `POST /api/admin/users/bulk-delete`
- Backups: import, bulk delete/restore, `GET .../verify`, SHA-256 on create + metadata fix (id hydration)

#### Tests
- PHPUnit: `BulkBatchResultTest`, content/trash bulk, backup verify path
- Vitest: `useBulkSelection.test.ts`

#### Docs
- [ITERATION_28.md](docs/ITERATION_28.md) — includes backup import/hash scope (extends It.20 backup ops)

#### Deferred (post 2.0.16)
- Bulk SEO patch
- Messages bulk mark-read

---

### Iteration 27 – Admin view modes + SEO metadata panel

#### Frontend
- Add `useAdminViewMode` + `AdminViewModeToggle` — `list` / `list-preview` / `preview` with per-section `localStorage` persistence
- **Media Library** — table modes + SEO health badges + “SEO issues only” filter; grid mode keeps inline metadata edit
- Add **`MediaMetadataModal`** — responsive edit dialog for list modes (fixes table column overlap on title/alt)
- **Articles / Pages** (`PagesManager`) — three view modes, preview cards, SEO badges and filter
- Add `SeoMetadataPanel`, `SeoHealthBadge`, `seoHealth` utils — title, description, OG image, robots, canonical in editor
- **`MarkdownEditor`** — Content / SEO tabs; saves SEO front matter on create/update

#### Backend
- `ContentController` — `applySeoFrontMatter()` + serialize `seoTitle`, `seoDescription`, `canonical`, `ogImage`, `noIndex`

#### Tests
- Vitest: `useAdminViewMode`, `seoHealth`, `MediaManager` (list modal save), extended manager tests

#### Docs
- [ITERATION_27.md](docs/ITERATION_27.md) — complete spec (audit API deferred to backlog)

#### Deferred (post 2.0.15)
- `GET /api/content/seo-audit` — list-wide SEO audit endpoint

---

## [2.0.14] – 2026-07-18

### Hotfix – Media preview display and strict format validation

#### Backend
- Add `FileWriter::writeBinary()` and `FileReader::readBinary()` — stop UTF-8 normalization corrupting JPEG/PNG uploads
- Add `MediaFormats` — strict MIME, extension, and magic-byte validation for allowed media types
- Add `GET /api/media/formats` and `GET /api/media/file/{path}` for admin same-origin image serving
- `MediaRepository::saveUpload()` uses binary I/O and `MediaFormats::validate()`

#### Frontend
- Admin thumbnails/lightbox use `/api/media/file/{path}` (same-origin); public embeds use `/storage/...`
- Load upload `accept` from `/api/media/formats`; fallback to `/storage/` on image load error
- Fix `MediaPickerModal` preview URLs and missing `useEffect` import

#### Tests
- `MediaFormatsTest`; extended `MediaRepositoryTest`, `MediaControllerTest`, `FileWriterTest`, Vitest URL helpers

#### Docs
- `ITERATION_26.md` Part 4 (hotfix notes; re-upload legacy media after deploy)

---

## [2.0.13] – 2026-07-18

### Iteration 26 – Media preview lightbox

#### Frontend
- Add `MediaPreviewLightbox` — Fit (viewport) and 1:1 (native pixel) preview modes
- Media Library: click thumbnail, Expand / 1:1 buttons, prev/next in filtered grid
- Show natural dimensions, MIME, file size in lightbox header

#### Tests
- `MediaPreviewLightbox.test.tsx`

#### Docs
- `ITERATION_26.md`, `ITERATION_25.md` (setup wizard deferred), `ITERATION_BACKLOG.md` (It.27+)

---

## [2.0.12] – 2026-07-18

### Iteration 24 – Full DAM v1 + stock library

#### Backend
- Folder-aware media storage with `media/folders.json` index and `.paginium-folder` markers
- `.meta.json` sidecar per asset (altText, title, folder)
- `GET/POST /api/media/folders`, `POST /api/media/bulk-delete`
- Settings group `media` (`allowedMimeTypes`, `maxUploadSizeKb`, `stockImageTopic`, `stockImagesEnabled`)
- Stock image catalog `stock-images.json` + `StockImageImporter` (topic-aware Unsplash import)
- `GET /api/media/stock-topics`, `POST /api/media/stock-import`

#### Frontend
- Media Library folder navigation (breadcrumb + folder cards)
- Bulk select/delete, title + alt metadata edit
- Stock topic select + **Generovať z knižnice**
- Extended `api/media.ts` (folders, bulk, metadata, stock)

#### Tests
- Extended `MediaRepositoryTest`, `MediaControllerTest`, `StockImageCatalogTest`, `StockImageImporterTest`, `MediaManager.test.tsx`

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

#### Backend – settings schema
- Add groups `smtp`, `notifications`, `connectors`, `monitoring` in `SettingsSchema`
- SMTP: host, port, encryption, credentials, from address
- Connectors: email, ntfy, Discord, Telegram, webhook (settings-driven enable)
- Monitoring: incident alerts, severity filter, failed-login / security notifications, traffic spike threshold
- Toast: enabled, position, duration, debug mode (exposed via `GET /api/settings/public`)
- Mask password fields in admin settings API; ignore masked placeholders on save

#### Backend – notification stack
- Add `SmtpTransport` (TLS/SSL, AUTH LOGIN), `NotificationFactory`, `IncidentNotifier`
- Channel adapters: `EmailAdapter`, `NtfyAdapter`, `DiscordAdapter`, `TelegramAdapter`, `WebhookAdapter`
- Add `NotificationController` — `GET /api/admin/notifications/overview`, `POST /api/admin/notifications/test`
- Wire `NotificationService` + adapters in `Http/Config/services.php`
- `AuditTrailService::logSecurityEvent()` forwards to `IncidentNotifier`
- Failed login triggers incident alert when monitoring enabled
- Password reset email via SMTP when configured; demo reset token only in dev/testing without SMTP

#### Backend – analytics
- Implement `Reporter`, `AnalyticsManager`, `RealtimeTracker`, `GeoIPService`, `DeviceDetector`
- Add `AnalyticsMiddleware` on public routes (skip API/admin paths)
- Add `AnalyticsController` — overview, chart, top pages/referrers
- Flat-file visit storage under analytics registry

#### Backend – auth integration
- `AuthController` receives settings, notifications, incident notifier for reset mail + alerts

#### Frontend
- Add `/notifications` — `NotificationsOverview` (connectors, test send, visit stats, link to Settings)
- Add `api/notifications.ts`, `api/analytics.ts`
- Toast UI from `NotificationContext` + public settings (`toastEnabled`, position, duration, debug)
- Auth UI: `RegisterModal`, `ForgotPasswordModal`, `ResetPasswordModal`, `ChangePasswordModal`
- `SettingsView` — `password` field type for SMTP/connector secrets

#### Tests
- PHPUnit: `NotificationFactoryTest`, `IncidentNotifierTest`; updated `AuthControllerTest` (reset password)
- Vitest: `notificationSettings.test.ts`

#### Docs
- [ITERATION_6.md](docs/ITERATION_6.md), [SETTINGS.md](docs/architecture/SETTINGS.md) (English rewrite)
- ROADMAP It.6 ✅

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
