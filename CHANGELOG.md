# Changelog

This canonical history records release facts supported by the supplied `CHANGELOG.md`. Detailed incident analysis remains in [ISSUES.md](docs/ISSUES.md); the latest exact source snapshot is preserved in `docs/meta/it18/SOURCE_UPDATES/CHANGELOG.md` when present locally.

## History rules

- Semantic-version order, newest first.
- One stable explicit anchor per release.
- Issue references point to canonical `docs/ISSUES.md#iss-xxx` anchors.
- Commit links appear only when the supplied source names them.
- No dedicated source entries exist for `2.0.15` and `2.0.41`; no history is invented.
- Long implementation narratives are intentionally kept outside this canonical index.

## Release index

| Release | Date | Scope |
|---|---:|---|
| [`2.1.0-beta.27`](#release-2-1-0-beta-27) | 2026-08-05 | Untrusted surfaces hardening (It.67) and Git publish modes (It.70) |
| [`2.1.0-beta.26`](#release-2-1-0-beta-26) | 2026-08-03 | Unified cache, HTTP ETag/304 validators, audit hardening |
| [`2.1.0-beta.23`](#release-2-1-0-beta-23) | 2026-07-30 | Layout Switch, layout settings, preview frame and page templates |
| [`2.1.0-beta.22`](#release-2-1-0-beta-22) | 2026-07-30 | Security write-time gates and Feature Gallery Phase 3 |
| [`2.1.0-beta.21`](#release-2-1-0-beta-21) | 2026-07-30 | Feature Gallery Phase 2 and production SEO/logging hardening |
| [`2.1.0-beta.20`](#release-2-1-0-beta-20) | 2026-07-29 | Feature Gallery Phase 1 and footer UX polish |
| [`2.1.0-beta.19`](#release-2-1-0-beta-19) | 2026-07-29 | Footer social links, SPA analytics beacon and LAN/CORS fixes |
| [`2.1.0-beta.18`](#release-2-1-0-beta-18) | 2026-07-29 | Inline newsletter footer and System Update compare/deploy automation |
| [`2.1.0-beta.17`](#release-2-1-0-beta-17) | 2026-07-28 | Newsletter preferences, release campaigns, subscribe modal and cookie consent |
| [`2.1.0-beta.16`](#release-2-1-0-beta-16) | 2026-07-28 | Newsletter v2 phases 1–3, BE↔FE wiring and test hygiene |
| [`2.1.0-beta.15`](#release-2-1-0-beta-15) | 2026-07-27 | System Update remote version check and audit fixes |
| [`2.1.0-beta.14`](#release-2-1-0-beta-14) | 2026-07-27 | Docker admin deployment permissions and cache hardening |
| [`2.1.0-beta.13`](#release-2-1-0-beta-13) | 2026-07-27 | Docker deployment path resolution hotfix and update UX |
| [`2.1.0-beta.12`](#release-2-1-0-beta-12) | 2026-07-27 | Admin System Update MVP and test-environment isolation |
| [`2.1.0-beta.11`](#release-2-1-0-beta-11) | 2026-07-27 | Demo security polish and editor profile normalization |
| [`2.1.0-beta.10`](#release-2-1-0-beta-10) | 2026-07-27 | Full-trial isolated demo sandbox |
| [`2.1.0-beta.9`](#release-2-1-0-beta-9) | 2026-07-27 | Production hardening, analytics/editor work, newsletter admin and demo deployment |
| [`2.1.0-beta.8`](#release-2-1-0-beta-8) | 2026-07-26 | Color schemes, appearance mode and themed public site |
| [`2.1.0-beta.7`](#release-2-1-0-beta-7) | 2026-07-26 | Dependency, CI, Vitest, ESLint and deployment-environment fixes |
| [`2.1.0-beta.6`](#release-2-1-0-beta-6) | 2026-07-24 | Stored-XSS hardening, backup Zip-Slip protection and deploy-script hygiene |
| [`2.1.0-beta.5`](#release-2-1-0-beta-5) | 2026-07-24 | Rich navigation, editor-save fix and sliding-session hardening |
| [`2.1.0-beta.4`](#release-2-1-0-beta-4) | 2026-07-24 | Automatic tags and meta-description generator with safe dependency updates |
| [`2.1.0-beta.3`](#release-2-1-0-beta-3) | 2026-07-24 | React Router security patch and PaginiumCMS information panel |
| [`2.1.0-beta.2`](#release-2-1-0-beta-2) | 2026-07-23 | Public Beta security gate and audit CSV sanitization |
| [`2.1.0-beta.1`](#release-2-1-0-beta-1) | 2026-07-23 | Public Beta 1 tester release |
| [`2.0.58`](#release-2-0-58) | 2026-07-23 | Beta infrastructure and maintainer readiness gate |
| [`2.0.57`](#release-2-0-57) | 2026-07-23 | Docker onboarding and user-documentation synchronization |
| [`2.0.56`](#release-2-0-56) | 2026-07-23 | Password confirmation in registration and admin user management |
| [`2.0.55`](#release-2-0-55) | 2026-07-23 | Contribution checklist, complete API barrel and CI lint |
| [`2.0.54`](#release-2-0-54) | 2026-07-23 | Core hook emitters, reference plugin and extension code policy |
| [`2.0.53`](#release-2-0-53) | 2026-07-23 | Scheduled content publishing |
| [`2.0.52`](#release-2-0-52) | 2026-07-23 | Branding, settings-based ACL and CI regression fixes |
| [`2.0.51`](#release-2-0-51) | 2026-07-23 | Date/timezone/DST handling, maintenance and admin log UX |
| [`2.0.50`](#release-2-0-50) | 2026-07-22 | Public-site localization according to the configured locale |
| [`2.0.49`](#release-2-0-49) | 2026-07-22 | Localized audit messages |
| [`2.0.48`](#release-2-0-48) | 2026-07-22 | Security audit hardening across encryption, SSRF, ACL, WAF and OTP |
| [`2.0.47`](#release-2-0-47) | 2026-07-22 | Operations, platform and editor localization with test-wrapper hotfix |
| [`2.0.46`](#release-2-0-46) | 2026-07-21 | Media/navigation/dashboard localization, analytics and logging fixes |
| [`2.0.45`](#release-2-0-45) | 2026-07-21 | Security settings, custom locales, avatars and authentication UX |
| [`2.0.44`](#release-2-0-44) | 2026-07-21 | Admin UI localization, grouped navigation and settings organization |
| [`2.0.43`](#release-2-0-43) | 2026-07-20 | Tiptap JSON storage, rendered HTML cache and editor image upload |
| [`2.0.42`](#release-2-0-42) | 2026-07-20 | Modular Markdown and WYSIWYG editor profiles |
| [`2.0.40`](#release-2-0-40) | 2026-07-20 | Frontend TypeScript CI hotfix |
| [`2.0.39`](#release-2-0-39) | 2026-07-20 | Smooth SPA reload and admin navigation |
| [`2.0.38`](#release-2-0-38) | 2026-07-20 | External plugin import, registry, hooks and routes |
| [`2.0.37`](#release-2-0-37) | 2026-07-20 | Content API filters and server-side public blog |
| [`2.0.36`](#release-2-0-36) | 2026-07-20 | Company information and contact-page map |
| [`2.0.35`](#release-2-0-35) | 2026-07-20 | Contact-form subjects and test coverage |
| [`2.0.34`](#release-2-0-34) | 2026-07-20 | Dashboard v2 KPI row and enriched overview API |
| [`2.0.33`](#release-2-0-33) | 2026-07-20 | Admin deep links and frontend/backend alignment |
| [`2.0.32`](#release-2-0-32) | 2026-07-20 | URL-synchronized filters, preview modal and reading time |
| [`2.0.31`](#release-2-0-31) | 2026-07-20 | Public blog pagination, admin filters and optional new-tab links |
| [`2.0.30`](#release-2-0-30) | 2026-07-19 | 2FA setup/login separation and authentication UX fixes |
| [`2.0.29`](#release-2-0-29) | 2026-07-19 | Session stability, cache administration, auth hardening and deployment fixes |
| [`2.0.28`](#release-2-0-28) | 2026-07-19 | Blueprint engine, Demo sandbox v2 and project philosophy |
| [`2.0.27`](#release-2-0-27) | 2026-07-19 | SSO, path ACL, search, OTP workflows, counts, connector auth and feeds |
| [`2.0.26`](#release-2-0-26) | 2026-07-19 | Internal WAF, structured logging, admin Logs and CI incident fixes |
| [`2.0.25`](#release-2-0-25) | 2026-07-19 | Admin list UX, inboxes, comment policy, navigation and PHPStan compatibility |
| [`2.0.24`](#release-2-0-24) | 2026-07-19 | Post-audit security hardening and QA cleanup |
| [`2.0.23`](#release-2-0-23) | 2026-07-18 | SEO preview image from Media and blog preview fix |
| [`2.0.22`](#release-2-0-22) | 2026-07-18 | Code Editor create, delete and restore |
| [`2.0.21`](#release-2-0-21) | 2026-07-18 | Code Editor, 2FA UX and developer-unlock fixes |
| [`2.0.20`](#release-2-0-20) | 2026-07-18 | Content cache correctness and admin content-list improvements |
| [`2.0.19`](#release-2-0-19) | 2026-07-18 | Admin user management and staff 2FA policy |
| [`2.0.18`](#release-2-0-18) | 2026-07-18 | Cron planner and Job Queue |
| [`2.0.17`](#release-2-0-17) | 2026-07-18 | Scheduled monitoring reports and log-incident scanning |
| [`2.0.16`](#release-2-0-16) | 2026-07-18 | Shared bulk-actions platform |
| [`2.0.14`](#release-2-0-14) | 2026-07-18 | Binary-safe media I/O and strict format validation |
| [`2.0.13`](#release-2-0-13) | 2026-07-18 | Media preview lightbox |
| [`2.0.12`](#release-2-0-12) | 2026-07-18 | Folder-aware media storage and metadata sidecars |
| [`2.0.11`](#release-2-0-11) | 2026-07-17 | SEO metadata engine |
| [`2.0.10`](#release-2-0-10) | 2026-07-17 | Trash management, brute-force lockout, RSS and sitemap |
| [`2.0.9`](#release-2-0-9) | 2026-07-17 | Unified API response contract |
| [`2.0.8`](#release-2-0-8) | 2026-07-16 | RBAC and maintenance-mode middleware |
| [`2.0.7`](#release-2-0-7) | 2026-07-16 | Flat-file content index, pagination and search API |
| [`2.0.6`](#release-2-0-6) | 2026-07-16 | PHPStan Level 8 backend compliance and safe I/O helpers |
| [`2.0.5`](#release-2-0-5) | 2026-07-15 | Navigation, comments and contact modules |
| [`2.0.4`](#release-2-0-4) | 2026-07-15 | Media Manager frontend |
| [`2.0.3`](#release-2-0-3) | 2026-07-15 | Code policy and Code Editor foundation |
| [`2.0.2`](#release-2-0-2) | 2026-07-15 | Admin dashboard, monitoring and realtime analytics |
| [`2.0.1`](#release-2-0-1) | 2026-07-15 | Settings schema for SMTP, notifications, connectors and monitoring |
| [`2.0.0`](#release-2-0-0) | 2026-07-14 | Flat-file core across the first five planned iterations |
| [`1.0.0`](#release-1-0-0) | Initial structure | Initial repository structure |

## [Unreleased]

### Fixed

- **`LocalFlatFileStorage::assertWithinBase()`** — allow logical paths when intermediate directories (e.g. `data/`) do not exist yet; fixes HTTP PHPUnit `settings.testing.json` failures in CI.
- **API barrel (It.17)** — register `git`, `shortcodes`, and `themes` in `frontend/src/api/index.ts`.
- **Demo dashboard storage metric** — `DEMO_MODE=true` no longer exposes host partition free space; `DemoStorageQuotaService` reports synthetic sandbox quota (default 2 GiB from `DEMO_STORAGE_QUOTA_BYTES`) based on `storage/app/demo/` usage only.

<a id="release-2-1-0-beta-27"></a>

## [2.1.0-beta.27] – 2026-08-05

Untrusted surfaces hardening and Git publish distribution (Iterations 67 + 70)

### Added (Iteration 67)

- **Shortcode definitions (67a):** `ShortcodeDefinitionManager`, flat-file registry under `data/shortcodes/definitions/`, admin API (`GET/PUT/DELETE /api/admin/shortcodes`, `POST /api/admin/shortcodes/preview`); `ShortcodeDefinitionPolicy` on API save and Code Editor paths.
- **Theme ZIP import (67b):** `ThemeImporter`, `ThemeManifestValidator`, `ThemeRegistry`; shared `UntrustedPolicyScanner` (plugin parity); admin API (`GET/POST /api/admin/themes/import`, `DELETE /api/admin/themes/{id}`).
- **CSP hygiene (67c):** `SecurityMiddleware` adds `frame-ancestors 'none'`, `base-uri 'self'`, and `form-action 'self'`; residual `style-src 'unsafe-inline'` documented as [ISS-124](docs/ISSUES.md#iss-124).
- **Hostile corpus (67d):** fixtures under `backend/tests/Fixtures/hostile/`; `scripts/security-regression.sh` extended and wired into `scripts/iteration-gate.sh`.
- FE typed clients: `frontend/src/api/shortcodes.ts`, `frontend/src/api/themes.ts`; smoke script `scripts/smoke-it67.sh`.

### Added (Iteration 70)

- **Git publish modes:** `disabled` (Classic default), `immediate`, and `queued` strategies via `engine.git*` settings (`gitEnabled`, `gitPublishStrategy`, `gitPublisher`, `gitRepositoryPath`, `gitRemote`, `gitBranch`, `gitPushEnabled`, `gitCommitMessageTemplate`).
- Core: `GitPublishService`, `LocalGitPublisher`, `PublishQueueStore` (`data/git/publish-queue.json`), `PublishPlanner`, `GitPublishDispatcher`, `GitPathValidator`, `GitCapabilityProbe`.
- Scheduler: `git.publish` job via `GitPublishHandler`.
- Admin API: `GET /api/admin/git/status`, `GET /api/admin/git/publish/preview`, `POST /api/admin/git/publish`, `POST /api/admin/git/publish/{jobId}/retry` (`git:publish` permission, ADMIN default).
- Content SSOT writes hook through `GitPublishDispatcher` — Git failure does not roll back stored content.
- FE: `frontend/src/api/git.ts`; Engine settings panel shows `gitProbe` (SK/EN).
- Tests: `GitPublishServiceTest`, `GitPublishTestHelper`; smoke script `scripts/smoke-it70.sh`.

### Fixed

- **Bootstrap admin email** — default `admin@paginium.local` in `bootstrap-admin.php`, `.env.example`, and `scripts/first-run.sh` (PHP 8.5 rejects `admin@localhost` before password check).
- **`PluginPolicyScanner`** — delegates to shared `UntrustedPolicyScanner` (no duplicate scan logic).

### Documentation

- [docs/en/ITERATION_67.md](docs/en/ITERATION_67.md), [docs/en/ITERATION_70.md](docs/en/ITERATION_70.md), backlog and Hybrid Engine wave updated; [ISS-124](docs/ISSUES.md#iss-124) for CSP residual risk.

### Release facts

- **Categories:** Added, Fixed, Documentation, Security
- **Technical identifiers:** `ShortcodeDefinitionManager`, `ThemeImporter`, `UntrustedPolicyScanner`, `GitPublishService`, `GitPublishDispatcher`, `git:publish`, `AppVersion`
- **Deferred follow-ups:** It.70 `github_api` publisher, publish release modal UI, It.48 static render hook; It.71 Performance Guard → next release

<a id="release-2-1-0-beta-26"></a>

## [2.1.0-beta.26] – 2026-08-03

Unified cache and HTTP conditional requests (Iteration 69)

### Added (Iteration 69)

- `CacheDriverInterface` with `health()`, `tagKey()`, and `invalidateTags()`; `MemoryDriver`, `FileDriver`, and `ChainedDriver` (`auto`).
- `CacheDriverFactory`, `CacheTagRegistry`, and `CacheCapabilityProbe`; `engine.cacheDriver`, `cacheDefaultTtlSeconds`, `httpValidatorsEnabled`.
- HTTP `ETag`, `Last-Modified`, and `Cache-Control` on public GET:
  - `/api/settings/public`
  - `/api/pages`, `/api/pages/{slug}`
  - `/api/articles`, `/api/articles/{slug}` (anonymous reads; `304` when `If-None-Match` matches).
- Deterministic content cache invalidation on write/publish/delete (generation bump + tags).
- Admin cache stats include hit/miss metrics; Engine panel cache probe (SK/EN).
- Runbook: [docs/en/runbooks/CACHE_OPERATIONS.md](docs/en/runbooks/CACHE_OPERATIONS.md).
- Absorbs legacy **It.45** and **It.49**; Redis driver deferred (`cacheDriver=redis` → `auto`).

### Fixed

- **`LocalFlatFileStorage::assertWithinBase()`** — missing storage root now fails closed instead of silently allowing I/O.
- **ISS-089** — documented **GHSA-qwww-vcr4-c8h2** (React Router RSC CSRF) as not applicable to the SPA profile; CI remains `--audit-level=critical`.
- **npm audit** — dev-only `brace-expansion` advisories resolved via `npm audit fix`.

### Release facts

- **Categories:** Added, Fixed, Documentation
- **Technical identifiers:** `HttpConditionalResponse`, `CacheTagRegistry`, `CacheDriverFactory`, `AppVersion`

<a id="release-2-1-0-beta-25"></a>

## [2.1.0-beta.25] – 2026-08-03

Release hotfix — version string, deploy script, admin update ref validation

### Fixed

- **`AppVersion::VERSION`** now matches the release tag (beta.24 incorrectly reported `2.1.0-beta.23` in `/api/health`).
- **`scripts/deploy-instance-update.sh`** — tag checkout backs up untracked blockers, supports `DEPLOY_FORCE=1`, uses `git checkout -f` for immutable release deploys.
- **Admin system update** — empty `ref` no longer silently defaults to `origin/main` when branch deploy is disabled; returns a clear **422** asking for a release tag.

### Release facts

- **Categories:** Fixed, Operations
- **Technical identifiers:** `AppVersion`, `deploy-instance-update.sh`, `DEPLOY_FORCE`, `SystemUpdateController::run`

<a id="release-2-1-0-beta-24"></a>

## [2.1.0-beta.24] – 2026-08-03

Hybrid Engine foundation (Iteration 68)

### Added (Iteration 68 — Hybrid Engine foundation)

- `StorageInterface`, `LocalFlatFileStorage`, and `StorageFactory` with allow-listed `local` driver and Classic bootstrap defaults.
- Settings `engine` group (`deploymentMode`, `storageDriver`, `schemaValidationEnabled`, `capabilityProbeEnabled`); missing `engine.*` keeps beta.23 behavior.
- `DocumentSchemaRegistry` + `DocumentValidator` with fail-closed `settings.overrides@1` JSON Schema validation.
- Settings persistence reads through `StorageInterface`; JSON content saves use the storage layer (Markdown path unchanged).
- Admin **Settings → Hybrid Engine** with capability probe panel (SK/EN).
- Regression/API tests: storage parity, symlink escape, corrupt overrides → `422`, engine capability probe.

### Documentation

- PaginiumCMS is documented as a **Hybrid Headless Content Engine** with a mandatory **No-SQL flat-file source of truth**.
- It.69–77 remain target capabilities; It.68 foundation is shipped in this release.
- Iteration 14 consolidates chronology, stable release anchors, issues and commit evidence.

### Security and operations

- Static SPA headers, CSRF exemption-boundary hardening, `expose_php = Off`, loopback-only trusted-proxy defaults, `/.well-known/security.txt`, and Docker reboot recovery.
- [ISS-120](docs/ISSUES.md#iss-120): backend CI uses `run-backend-tests-ci.sh` → `sanitize-ci-log.py` → `verify-ci-log-redaction.sh`; raw output is not published and the local workflow is documented in [`LOCAL_TEST_LOGS.md.example`](LOCAL_TEST_LOGS.md.example).

### Fixed

- [ISS-112](docs/ISSUES.md#iss-112): Unix-second lock timestamps are no longer interpreted as milliseconds.
- [ISS-121](docs/ISSUES.md#iss-121): invalid settings group shapes are no longer silently dropped; fail-closed JSON Schema validation returns HTTP **422**.
- [ISS-122](docs/ISSUES.md#iss-122): `LocalFlatFileStorage` enforces base-path containment on all public methods, including `read()`.
- [ISS-123](docs/ISSUES.md#iss-123): HTTP PHPUnit tests reset `settings.testing.json` so corrupt-state cases do not leak between tests.

### Release facts

- **Tag commit:** `e6790cc`
- **Note:** `/api/health` still reported `2.1.0-beta.23` until **beta.25** (`AppVersion` bump omitted).
- **Categories:** Added, Documentation, Security, Fixed
- **Technical identifiers:** `StorageInterface`, `LocalFlatFileStorage`, `DocumentSchemaRegistry`, `settings.overrides@1`, `engine.deploymentMode`, `GET /api/admin/settings/engine`, `EngineSettingsPanel`, `EngineCapabilityProbe`

<a id="release-2-1-0-beta-23"></a>

## [2.1.0-beta.23] – 2026-07-30

Layout Switch, layout settings, preview frame and page templates

### Release facts

- **Recorded source items:** 5
- **Categories:** Added, Documentation
- **Technical identifiers:** `developerRequiresAdmin`, `LayoutPreviewFrame`, `PageLayoutCatalog`, `appearance.previewTemplate`

<a id="release-2-1-0-beta-22"></a>

## [2.1.0-beta.22] – 2026-07-30

Security write-time gates and Feature Gallery Phase 3

### Release facts

- **Recorded source items:** 12
- **Categories:** Added, Security, Documentation
- **Technical identifiers:** `GET /api/admin/gallery/export`, `POST /api/admin/gallery/import`, `gallery:manage`, `FeatureGallerySection`, `run-all-tests.zsh`, `./scripts/security-regression.sh`, `security-static-grep.sh`, `CodePolicyEngine`, `data/layout|shortcodes|plugins`, `strict_types`

### Related incidents

- [ISS-008](docs/ISSUES.md#iss-008)

<a id="release-2-1-0-beta-21"></a>

## [2.1.0-beta.21] – 2026-07-30

Feature Gallery Phase 2 and production SEO/logging hardening

### Release facts

- **Recorded source items:** 13
- **Categories:** Added, Fixed, Documentation
- **Technical identifiers:** `autoplayIntervalMs`, `modalCaptionStyle`, `/api/settings/public`, `/funkcie`, `/gallery`, `ContentController`, `SeoController`, `content.page.{slug}`, `content.*.payload.{slug}`, `paginiumcms.com`

### Related incidents

- [ISS-110](docs/ISSUES.md#iss-110), [ISS-111](docs/ISSUES.md#iss-111)

<a id="release-2-1-0-beta-20"></a>

## [2.1.0-beta.20] – 2026-07-29

Feature Gallery Phase 1 and footer UX polish

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Changed, Documentation
- **Technical identifiers:** `/gallery`, `/features`, `GET /api/gallery/public`, `GET/POST/PUT/DELETE /api/admin/gallery/*`, `gallery:manage`, `data/gallery/index.json`, `data/gallery/items/{id}.json`

<a id="release-2-1-0-beta-19"></a>

## [2.1.0-beta.19] – 2026-07-29

Footer social links, SPA analytics beacon and LAN/CORS fixes

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Fixed, Documentation
- **Technical identifiers:** `GET /api/settings/public`, `social.enabled`, `social.links[]`, `AnalyticsMiddleware`, `POST /api/analytics/pageview`, `useAnalyticsPageview`, `PublicSiteLayout`, `:8081/`, `192.168.10.20:3025`, `localhost:5173`

<a id="release-2-1-0-beta-18"></a>

## [2.1.0-beta.18] – 2026-07-29

Inline newsletter footer and System Update compare/deploy automation

### Release facts

- **Recorded source items:** 4
- **Categories:** Changed, Added
- **Technical identifiers:** `POST /api/webhooks/github/release`

### Related incidents

- [ISS-109](docs/ISSUES.md#iss-109)

<a id="release-2-1-0-beta-17"></a>

## [2.1.0-beta.17] – 2026-07-28

Newsletter preferences, release campaigns, subscribe modal and cookie consent

### Release facts

- **Recorded source items:** 8
- **Categories:** Added
- **Technical identifiers:** `/newsletter`, `NewsletterSettingsPanel`, `GET/POST /api/newsletter/manage`, `/newsletter/manage?token=…`, `GET /api/newsletter/unsubscribe?token=&preference=`, `cmsReleaseEnabled`, `POST /api/admin/newsletter/send/cms-release`, `/api/newsletter/*`

<a id="release-2-1-0-beta-16"></a>

## [2.1.0-beta.16] – 2026-07-28

Newsletter v2 phases 1–3, BE↔FE wiring and test hygiene

### Release facts

- **Recorded source items:** 32
- **Categories:** Added, Fixed, Security
- **Technical identifiers:** `requireDoubleOptIn`, `GET /api/newsletter/confirm`, `/newsletter/confirm`, `GET /api/newsletter/unsubscribe`, `/newsletter/unsubscribe`, `confirmTokenTtlHours`, `NewsletterMailService`, `NotificationService`, `newsletter.weekly_digest`, `NewsletterWeeklyDigestHandler`

### Related incidents

- [ISS-106](docs/ISSUES.md#iss-106), [ISS-107](docs/ISSUES.md#iss-107), [ISS-108](docs/ISSUES.md#iss-108)

<a id="release-2-1-0-beta-15"></a>

## [2.1.0-beta.15] – 2026-07-27

System Update remote version check and audit fixes

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Security, Tests/verification
- **Technical identifiers:** `SystemUpdateVersionMatcher`, `SystemUpdateRemote`, `system.deploy`, `SUPER_ADMIN`, `GeoIPService`, `OutboundUrlGuard`, `SystemUpdateVersionMatcherTest`, `SystemUpdateControllerTest`, `JobRegistryStoreTest`, `JobsControllerPrivilegedDeployTest`

### Related incidents

- [ISS-104](docs/ISSUES.md#iss-104), [ISS-105](docs/ISSUES.md#iss-105)

<a id="release-2-1-0-beta-14"></a>

## [2.1.0-beta.14] – 2026-07-27

Docker admin deployment permissions and cache hardening

### Release facts

- **Recorded source items:** 5
- **Categories:** Added, Fixed, Documentation
- **Technical identifiers:** `scripts/bootstrap-deploy-permissions.sh`, `deploy-instance-update.sh`, `safe.directory`, `COMPOSER_HOME`, `backend/storage/app/deploy-cache`, `stack.sh`, `SystemDeployService`, `set_time_limit(0)`

<a id="release-2-1-0-beta-13"></a>

## [2.1.0-beta.13] – 2026-07-27

Docker deployment path resolution hotfix and update UX

### Release facts

- **Recorded source items:** 2
- **Categories:** Fixed
- **Technical identifiers:** `AppRoot`, `/var/www/html`, `APP_ROOT`, `.env`, `missing_script`

<a id="release-2-1-0-beta-12"></a>

## [2.1.0-beta.12] – 2026-07-27

Admin System Update MVP and test-environment isolation

### Release facts

- **Recorded source items:** 11
- **Categories:** Added, Fixed, Security, Documentation
- **Technical identifiers:** `GET/POST /api/admin/system/update/*`, `SUPER_ADMIN`, `SystemUpdateView`, `/platform/update`, `system:deploy --ref=`, `SystemDeployService`, `scripts/deploy-instance-update.sh`, `.env`, `DEMO_MODE=true`, `APP_ENV=testing`

### Related incidents

- [ISS-103](docs/ISSUES.md#iss-103)

<a id="release-2-1-0-beta-11"></a>

## [2.1.0-beta.11] – 2026-07-27

Demo security polish and editor profile normalization

### Release facts

- **Recorded source items:** 8
- **Categories:** Fixed, Added, Changed, Documentation
- **Technical identifiers:** `capabilities: { enabled: [] }`, `GET /api/settings/public`, `POST /api/demo/quick-login`, `demo.paginiumcms.com`, `/demo`

### Related incidents

- [ISS-100](docs/ISSUES.md#iss-100)

<a id="release-2-1-0-beta-10"></a>

## [2.1.0-beta.10] – 2026-07-27

Full-trial isolated demo sandbox

### Release facts

- **Recorded source items:** 8
- **Categories:** Added, Changed, Operations/notes
- **Technical identifiers:** `storage/app/demo/`, `GET /api/demo/public-info`, `DemoPublicStrip`, `demoFooterLinkEnabled`, `next_reset_at`, `seconds_until_reset`, `/demo`

### Evidence

- [`ab5b5fb`](https://github.com/techberode/paginiumcms-architecture/commit/ab5b5fb)

<a id="release-2-1-0-beta-9"></a>

## [2.1.0-beta.9] – 2026-07-27

Production hardening, analytics/editor work, newsletter admin and demo deployment

### Release facts

- **Recorded source items:** 34
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `RefererAnalyzer`, `AnalyticsIpMasker`, `geo_visits`, `EditorComponentRegistry`, `editor.components[]`, `EditorCustomComponentsPanel`, `:::hello-widget`, `POST /api/newsletter/subscribe`, `source: footer`, `GET /api/admin/newsletter/subscribers`

### Related incidents

- [ISS-094](docs/ISSUES.md#iss-094), [ISS-095](docs/ISSUES.md#iss-095), [ISS-097](docs/ISSUES.md#iss-097), [ISS-098](docs/ISSUES.md#iss-098), [ISS-099](docs/ISSUES.md#iss-099)

### Evidence

- [`a492e53`](https://github.com/techberode/paginiumcms-architecture/commit/a492e53), [`0fe21ec`](https://github.com/techberode/paginiumcms-architecture/commit/0fe21ec)

<a id="release-2-1-0-beta-8"></a>

## [2.1.0-beta.8] – 2026-07-26

Color schemes, appearance mode and themed public site

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `GET /api/settings/public`, `frontend/src/theme/`, `colorSchemes.ts`, `applyColorScheme.ts`, `publicUiClasses.ts`, `defaultTokens.css`, `docs/architecture/THEMES.md`, `AppVersion`

### Related incidents

- [ISS-089](docs/ISSUES.md#iss-089), [ISS-093](docs/ISSUES.md#iss-093)

<a id="release-2-1-0-beta-7"></a>

## [2.1.0-beta.7] – 2026-07-26

Dependency, CI, Vitest, ESLint and deployment-environment fixes

### Release facts

- **Recorded source items:** 7
- **Categories:** Fixed, Changed, Operations/notes
- **Technical identifiers:** `react-router@8.3.0`, `eslint@^9.39.0`, `.gitignore`, `deploy-frontend-lan.env.local`, `:?`, `react-router-dom@7.18.1`, `react-router@7.18.1`, `AppVersion`, `docs/ITERATION_58_ALTERNATIVES.md`

### Related incidents

- [ISS-078](docs/ISSUES.md#iss-078), [ISS-089](docs/ISSUES.md#iss-089), [ISS-090](docs/ISSUES.md#iss-090), [ISS-091](docs/ISSUES.md#iss-091), [ISS-092](docs/ISSUES.md#iss-092)

<a id="release-2-1-0-beta-6"></a>

## [2.1.0-beta.6] – 2026-07-24

Stored-XSS hardening, backup Zip-Slip protection and deploy-script hygiene

### Release facts

- **Recorded source items:** 6
- **Categories:** Fixed, Changed
- **Technical identifiers:** `strip_tags()`, `HtmlDomSanitizer`, `sanitizePublicHtml()`, `dangerouslySetInnerHTML`, `BackupManager::importBackup()`, `ZipEntryGuard`, `deploy-frontend-lan.sh`, `DEPLOY_HOST`, `DEPLOY_USER`, `HtmlDomSanitizer::isSafeUri()`

### Related incidents

- [ISS-086](docs/ISSUES.md#iss-086), [ISS-087](docs/ISSUES.md#iss-087), [ISS-088](docs/ISSUES.md#iss-088)

<a id="release-2-1-0-beta-5"></a>

## [2.1.0-beta.5] – 2026-07-24

Rich navigation, editor-save fix and sliding-session hardening

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `NavigationItemRichFields`, `NavMenuVisual`, `SettingsSchema`, `settings/sk.ts`, `settings/en.ts`, `EditorContentValidator`, `DemoMode`, `SessionManager::refreshCookieLifetime()`, `paginium:auth-expired`, `AppVersion`

### Related incidents

- [ISS-079](docs/ISSUES.md#iss-079), [ISS-084](docs/ISSUES.md#iss-084), [ISS-085](docs/ISSUES.md#iss-085)

<a id="release-2-1-0-beta-4"></a>

## [2.1.0-beta.4] – 2026-07-24

Automatic tags and meta-description generator with safe dependency updates

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Changed, Operations/notes
- **Technical identifiers:** `POST /api/admin/content/suggest-meta`, `autoTagEnabled`, `autoTagMax`, `autoDescriptionEnabled`, `autoDescriptionMaxLength`, `AppVersion`, `league/commonmark`, `@tiptap/*`, `^7.0`

<a id="release-2-1-0-beta-3"></a>

## [2.1.0-beta.3] – 2026-07-24

React Router security patch and PaginiumCMS information panel

### Release facts

- **Recorded source items:** 6
- **Categories:** Fixed, Added, Changed
- **Technical identifiers:** `composer.json`, `"license": "MIT"`, `AppVersion`, `BrowserRouter`, `MemoryRouter`

### Related incidents

- [ISS-078](docs/ISSUES.md#iss-078)

<a id="release-2-1-0-beta-2"></a>

## [2.1.0-beta.2] – 2026-07-23

Public Beta security gate and audit CSV sanitization

### Release facts

- **Recorded source items:** 2
- **Categories:** Fixed, Changed
- **Technical identifiers:** `AuditTrailService::exportAuditToCsv()`, `LogSanitizer`, `AuditTrailServiceTest`, `AppVersion`

<a id="release-2-1-0-beta-1"></a>

## [2.1.0-beta.1] – 2026-07-23

Public Beta 1 tester release

### Release facts

- **Recorded source items:** 4
- **Categories:** Added, Changed
- **Technical identifiers:** `docs/PUBLIC_BETA1.md`, `docs/user/BETA_TESTER.md`, `README.md`, `CONTINUATION.md`, `user/README.md`, `BETA_INFRA.md`, `AppVersion`

<a id="release-2-0-58"></a>

## [2.0.58] – 2026-07-23

Beta infrastructure and maintainer readiness gate

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Changed
- **Technical identifiers:** `docs/deploy/CRON.md`, `scheduler:run`, `worker:process`, `docs/developer/BETA_INFRA.md`, `scripts/iteration-gate.sh`, `npm run lint:api-barrel`, `docs/user/README.md`, `docs/user/INSTALLATION.md`, `docs/deploy/DEV.md`, `TESTING.md`

<a id="release-2-0-57"></a>

## [2.0.57] – 2026-07-23

Docker onboarding and user-documentation synchronization

### Release facts

- **Recorded source items:** 8
- **Categories:** Changed
- **Technical identifiers:** `README.md`, `docs/README.md`, `scripts/first-run.sh`, `docs/developer/LOCAL_SETUP.md`, `FIRST_ADMIN_*`, `INSTALL_FRONTEND`, `docs/user/INSTALLATION.md`, `.env`, `.env.example`, `docs/CONTINUATION.md`

<a id="release-2-0-56"></a>

## [2.0.56] – 2026-07-23

Password confirmation in registration and admin user management

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `ValidationRules::validatePasswordConfirmation()`, `password_confirm`, `RegisterModal`, `AuthController`, `UsersManager`, `UserController`, `validatePasswordConfirmation()`, `utils/validation.ts`, `CoreHardeningTest`, `TestCase`

### Related incidents

- [ISS-076](docs/ISSUES.md#iss-076)

### Evidence

- [`0664ba3`](https://github.com/techberode/paginiumcms-architecture/commit/0664ba3)

<a id="release-2-0-55"></a>

## [2.0.55] – 2026-07-23

Contribution checklist, complete API barrel and CI lint

### Release facts

- **Recorded source items:** 6
- **Categories:** Added, Changed
- **Technical identifiers:** `docs/developer/CONTRIBUTING.md`, `frontend/scripts/lint-api-barrel.mjs`, `npm run lint:api-barrel`, `api.*`, `.github/workflows/ci.yml`, `frontend/src/api/index.ts`, `docs/ITERATION_17.md`, `AppVersion`

<a id="release-2-0-54"></a>

## [2.0.54] – 2026-07-23

Core hook emitters, reference plugin and extension code policy

### Release facts

- **Recorded source items:** 11
- **Categories:** Added, Changed, Fixed
- **Technical identifiers:** `HookCatalog`, `HookEmitter`, `ExtensionManifestValidator`, `plugin.json`, `minCmsVersion`, `AppVersion`, `GET /api/extensions/hello-widget/ping`, `docs/developer/EXTENSION_CODE_POLICY.md`, `HookEmitterTest`, `ExtensionManifestValidatorTest`

### Related incidents

- [ISS-075](docs/ISSUES.md#iss-075)

<a id="release-2-0-53"></a>

## [2.0.53] – 2026-07-23

Scheduled content publishing

### Release facts

- **Recorded source items:** 7
- **Categories:** Added, Changed
- **Technical identifiers:** `content.scheduled_publish`, `ContentScheduledPublishService`, `status: scheduled`, `publishApprovedAt`

<a id="release-2-0-52"></a>

## [2.0.52] – 2026-07-23

Branding, settings-based ACL and CI regression fixes

### Release facts

- **Recorded source items:** 10
- **Categories:** Added, Changed, Fixed
- **Technical identifiers:** `SiteLogo`, `SiteBrandingHead`, `acl.json`, `AuthorizationManager`, `/security/acl`, `GET/PUT /api/admin/security/acl`, `GET /api/admin/security/audit`, `security.php`, `LoginAttemptTracker::clearAll()`, `Http\TestCase::setUp`

### Related incidents

- [ISS-055](docs/ISSUES.md#iss-055), [ISS-072](docs/ISSUES.md#iss-072), [ISS-073](docs/ISSUES.md#iss-073), [ISS-074](docs/ISSUES.md#iss-074)

<a id="release-2-0-51"></a>

## [2.0.51] – 2026-07-23

Date/timezone/DST handling, maintenance and admin log UX

### Release facts

- **Recorded source items:** 20
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `useCachePurge`, `TimezoneSelect`, `ComingSoonPage`, `UnderMaintenancePage`, `POST /api/maintenance/newsletter`, `POST /api/maintenance/message`, `/logs`, `POST /api/admin/logs/bulk`, `POST /api/admin/logs/delete-all`, `contentDates.ts`

### Related incidents

- [ISS-063](docs/ISSUES.md#iss-063), [ISS-064](docs/ISSUES.md#iss-064), [ISS-065](docs/ISSUES.md#iss-065), [ISS-066](docs/ISSUES.md#iss-066), [ISS-067](docs/ISSUES.md#iss-067), [ISS-068](docs/ISSUES.md#iss-068), [ISS-069](docs/ISSUES.md#iss-069), [ISS-070](docs/ISSUES.md#iss-070), [ISS-071](docs/ISSUES.md#iss-071)

<a id="release-2-0-50"></a>

## [2.0.50] – 2026-07-22

Public-site localization according to the configured locale

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Changed, Fixed, Tests/verification
- **Technical identifiers:** `general.language`, `frontend/src/i18n/modules/public/{sk,en}.ts`, `frontend/src/i18n/modules/public/public.test.ts`, `BlogRenderer`, `PageRenderer`, `ContactForm`, `SiteSearchModal`, `ArticleComments`, `CompanyInfoPanel`, `PublicSiteLayout`

### Related incidents

- [ISS-062](docs/ISSUES.md#iss-062)

<a id="release-2-0-49"></a>

## [2.0.49] – 2026-07-22

Localized audit messages

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Changed, Fixed, Tests/verification
- **Technical identifiers:** `general.language`, `backend/lang/{sk,en}/audit.php`, `frontend/src/i18n/modules/audit/{sk,en}.ts`, `system_event`, `AuditMessageFormatter`, `Lang::get()`, `formatFromLog()`, `context.summary`, `AuditTrailService`, `buildDiffMetadata()`

<a id="release-2-0-48"></a>

## [2.0.48] – 2026-07-22

Security audit hardening across encryption, SSRF, ACL, WAF and OTP

### Release facts

- **Recorded source items:** 11
- **Categories:** Security, Fixed, Tests/verification
- **Technical identifiers:** `EncryptionService`, `enc:s1:`, `twoFactorSecret`, `data/`, `backend/public/`, `backend/storage/.htaccess`, `LogSanitizer`, `OutboundUrlGuard`, `ContentPathAclGuard`, `FirewallRequestBodyReader`

### Related incidents

- [ISS-012](docs/ISSUES.md#iss-012), [ISS-052](docs/ISSUES.md#iss-052), [ISS-053](docs/ISSUES.md#iss-053), [ISS-054](docs/ISSUES.md#iss-054), [ISS-055](docs/ISSUES.md#iss-055), [ISS-056](docs/ISSUES.md#iss-056), [ISS-057](docs/ISSUES.md#iss-057), [ISS-058](docs/ISSUES.md#iss-058)

<a id="release-2-0-47"></a>

## [2.0.47] – 2026-07-22

Operations, platform and editor localization with test-wrapper hotfix

### Release facts

- **Recorded source items:** 14
- **Categories:** Added, Fixed, Tests/verification
- **Technical identifiers:** `src/i18n/modules/{comments,messages,backups,trash,logs}/{sk,en}.ts`, `src/i18n/modules/platform/{sk,en}.ts`, `src/i18n/modules/editor/{sk,en}.ts`, `src/i18n/modules/dashboard/{sk,en}.ts`, `src/i18n/core/{sk,en}.ts`, `summarizeBulkResult`, `ops18f.test.ts`, `platform.test.ts`, `editor.test.ts`, `settings/en.ts`

### Related incidents

- [ISS-059](docs/ISSUES.md#iss-059), [ISS-060](docs/ISSUES.md#iss-060)

### Evidence

- [`f0a885c`](https://github.com/techberode/paginiumcms-architecture/commit/f0a885c), [`390b392`](https://github.com/techberode/paginiumcms-architecture/commit/390b392)

<a id="release-2-0-46"></a>

## [2.0.46] – 2026-07-21

Media/navigation/dashboard localization, analytics and logging fixes

### Release facts

- **Recorded source items:** 31
- **Categories:** Added, Fixed, Documentation
- **Technical identifiers:** `src/i18n/modules/media/{sk,en}.ts`, `src/i18n/modules/navigation/{sk,en}.ts`, `src/i18n/modules/dashboard/{sk,en}.ts`, `MediaManager.tsx`, `NavigationManager.tsx`, `DashboardView.tsx`, `AnalyticsView`, `/analytics`, `DashboardDiskStructurePanel`, `ContentStorageStatsService`

### Related incidents

- [ISS-046](docs/ISSUES.md#iss-046), [ISS-047](docs/ISSUES.md#iss-047), [ISS-048](docs/ISSUES.md#iss-048), [ISS-049](docs/ISSUES.md#iss-049), [ISS-050](docs/ISSUES.md#iss-050)

<a id="release-2-0-45"></a>

## [2.0.45] – 2026-07-21

Security settings, custom locales, avatars and authentication UX

### Release facts

- **Recorded source items:** 10
- **Categories:** Added, Fixed
- **Technical identifiers:** `UploadSecurityValidator`, `ContentSecuritySanitizer`, `ContentBodyRenderer`, `AuthShell`, `TotpCodeInput`, `SettingsBackedPasswordPolicy`, `/api/validation/rules/password`, `ADMIN_DEFAULT_ROUTE`, `SupportedLocalesRegistry`, `LocaleScaffoldService`

### Related incidents

- [ISS-044](docs/ISSUES.md#iss-044), [ISS-045](docs/ISSUES.md#iss-045)

<a id="release-2-0-44"></a>

## [2.0.44] – 2026-07-21

Admin UI localization, grouped navigation and settings organization

### Release facts

- **Recorded source items:** 11
- **Categories:** Added, Fixed, Tests/verification
- **Technical identifiers:** `/translations`, `backend/lang`, `frontend/src/i18n`, `/api/admin/translations/*`, `.err`, `use PaginiumCMS\Core\Hook\HookManager`, `services.php`, `TestI18nProvider`, `TranslationFileManagerTest`, `TranslationControllerTest`

<a id="release-2-0-43"></a>

## [2.0.43] – 2026-07-20

Tiptap JSON storage, rendered HTML cache and editor image upload

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Changed, Tests/verification
- **Technical identifiers:** `TiptapHtmlRenderer`, `ContentBodyRenderer`, `contentFormat: tiptap_json`, `JsonContentStorage`, `authApi.probeSessionWithRetry()`, `tiptap_json`, `MarkdownParser`, `TiptapHtmlRendererTest`, `ContentBodyRendererTest`, `EditorContentValidatorTest`

### Related incidents

- [ISS-042](docs/ISSUES.md#iss-042)

<a id="release-2-0-42"></a>

## [2.0.42] – 2026-07-20

Modular Markdown and WYSIWYG editor profiles

### Release facts

- **Recorded source items:** 8
- **Categories:** Added, Fixed, Tests/verification
- **Technical identifiers:** `Core/Editor/`, `EditorProfileService`, `EditorContentValidator`, `editor.defaultProfilePage`, `editor.defaultProfileArticle`, `editor.profiles`, `EditorProfilePicker`, `EditorProfileServiceTest`, `EditorContentValidatorTest`, `ContentControllerTest`

<a id="release-2-0-40"></a>

## [2.0.40] – 2026-07-20

Frontend TypeScript CI hotfix

### Release facts

- **Recorded source items:** 2
- **Categories:** Fixed
- **Technical identifiers:** `PagesManager.tsx`, `useAdminListQuery`, `docs/ISSUES.md`

### Related incidents

- [ISS-041](docs/ISSUES.md#iss-041)

<a id="release-2-0-39"></a>

## [2.0.39] – 2026-07-20

Smooth SPA reload and admin navigation

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Changed, Tests/verification
- **Technical identifiers:** `@tanstack/react-query`, `useAdminListQuery`, `AdminPageSkeleton`, `AdminListSkeleton`, `ResponsiveLayout`, `DebugRouteTracker`, `MarkdownEditor`, `window.location.reload()`, `ArticleComments`, `SettingsContext.reload()`

<a id="release-2-0-38"></a>

## [2.0.38] – 2026-07-20

External plugin import, registry, hooks and routes

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Fixed
- **Technical identifiers:** `PluginRegistry`, `PluginImporter`, `PluginPolicyScanner`, `PluginManager`, `Http/Extensions/`, `data/plugins.json`, `GET/POST /api/admin/extensions`, `CodePolicyEngine`, `bootEnabledExtensions()`, `Http/Routes/extensions/{id}.php`

### Related incidents

- [ISS-039](docs/ISSUES.md#iss-039)

<a id="release-2-0-37"></a>

## [2.0.37] – 2026-07-20

Content API filters and server-side public blog

### Release facts

- **Recorded source items:** 5
- **Categories:** Added, Fixed
- **Technical identifiers:** `date_from`, `date_to`, `GET /api/articles`, `total_published`, `BlogRenderer`, `PublicSiteContext`, `blogSortToApiSort()`

<a id="release-2-0-36"></a>

## [2.0.36] – 2026-07-20

Company information and contact-page map

### Release facts

- **Recorded source items:** 5
- **Categories:** Added
- **Technical identifiers:** `mapEmbedUrl`, `GET /api/settings/public`, `CompanyInfoPanel`, `CompanyMapEmbed`, `PageRenderer`, `isSafeMapEmbedUrl`

<a id="release-2-0-35"></a>

## [2.0.35] – 2026-07-20

Contact-form subjects and test coverage

### Release facts

- **Recorded source items:** 2
- **Categories:** Added, Operations/notes
- **Technical identifiers:** `contactSubjects.test.ts`, `ContactForm.test.tsx`, `settings.contact`, `contact.subjects`, `allowCustomSubject`, `contact.*`

<a id="release-2-0-34"></a>

## [2.0.34] – 2026-07-20

Dashboard v2 KPI row and enriched overview API

### Release facts

- **Recorded source items:** 6
- **Categories:** Added, Changed
- **Technical identifiers:** `GET /api/admin/dashboard/overview`, `messages_unread`, `storage.free_space`, `/messages`, `/media`, `DashboardActivityPanel`, `/audit`, `AdminCountsService`, `docs/ISSUES.md`, `.cursorrules`

### Related incidents

- [ISS-037](docs/ISSUES.md#iss-037)

<a id="release-2-0-33"></a>

## [2.0.33] – 2026-07-20

Admin deep links and frontend/backend alignment

### Release facts

- **Recorded source items:** 7
- **Categories:** Fixed, Added
- **Technical identifiers:** `AuditTrail`, `/audit/content/:contentId`, `/audit/user/:userId`, `SettingsView`, `/settings?group={key}`, `location.state.group`, `LogsManager`, `frontend/src/utils/adminDeepLinks.ts`, `AdminRouteCatalog`, `/security/audit`

<a id="release-2-0-32"></a>

## [2.0.32] – 2026-07-20

URL-synchronized filters, preview modal and reading time

### Release facts

- **Recorded source items:** 10
- **Categories:** Added, Changed, Tests/verification
- **Technical identifiers:** `useMediaListQueryParams`, `useAdminListQueryParams`, `SitePreviewModal`, `content.showReadingTime`, `contact.subjects`, `contact.allowCustomSubject`, `ArticleTagsEditor`, `sitePreview.test.ts`, `readingTime.test.ts`, `useAdminListQueryParams.test.tsx`

<a id="release-2-0-31"></a>

## [2.0.31] – 2026-07-20

Public blog pagination, admin filters and optional new-tab links

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Changed, Tests/verification
- **Technical identifiers:** `content.blogItemsPerPage`, `itemsPerPage`, `/blog?page=&tag=&sort=`, `ui.openLinksInNewTab`, `AdminListFilterBar`, `useAdminListQueryParams`, `blogArticles.ts`, `linkTarget.ts`, `useOpenLinksInNewTab`, `BlogRenderer`

<a id="release-2-0-30"></a>

## [2.0.30] – 2026-07-19

2FA setup/login separation and authentication UX fixes

### Release facts

- **Recorded source items:** 18
- **Categories:** Added, Fixed, Changed
- **Technical identifiers:** `TwoFactorPolicy`, `TWO_FACTOR_REQUIRED`, `APP_ENV=development|local|testing`, `GET /api/auth/2fa/status`, `setup_pending`, `ProtectedRoute`, `/account/security`, `paginium:totp-required`, `paginium:auth-expired`, `twoFactorEnabled=true`

### Related incidents

- [ISS-029](docs/ISSUES.md#iss-029), [ISS-030](docs/ISSUES.md#iss-030), [ISS-031](docs/ISSUES.md#iss-031), [ISS-032](docs/ISSUES.md#iss-032), [ISS-033](docs/ISSUES.md#iss-033), [ISS-034](docs/ISSUES.md#iss-034), [ISS-035](docs/ISSUES.md#iss-035), [ISS-036](docs/ISSUES.md#iss-036)

### Evidence

- [`3fbc595`](https://github.com/techberode/paginiumcms-architecture/commit/3fbc595)

<a id="release-2-0-29"></a>

## [2.0.29] – 2026-07-19

Session stability, cache administration, auth hardening and deployment fixes

### Release facts

- **Recorded source items:** 19
- **Categories:** Added, Fixed, Changed, Operations/notes
- **Technical identifiers:** `SecureSessionManager`, `ClientIpResolver`, `SESSION_STRICT=false`, `AuthenticationManager::touchSession()`, `AuthMiddleware`, `CacheAdminService`, `CacheController`, `GET/POST /api/admin/cache/*`, `CacheManagerPanel`, `php backend/bin/console security:clear-lockouts`

### Related incidents

- [ISS-023](docs/ISSUES.md#iss-023), [ISS-024](docs/ISSUES.md#iss-024), [ISS-025](docs/ISSUES.md#iss-025), [ISS-026](docs/ISSUES.md#iss-026), [ISS-027](docs/ISSUES.md#iss-027), [ISS-028](docs/ISSUES.md#iss-028), [ISS-029](docs/ISSUES.md#iss-029)

<a id="release-2-0-28"></a>

## [2.0.28] – 2026-07-19

Blueprint engine, Demo sandbox v2 and project philosophy

### Release facts

- **Recorded source items:** 18
- **Categories:** Added, Documentation
- **Technical identifiers:** `BlueprintRepository`, `data/blueprints/{type}.json`, `DynamicValidator`, `GET/PUT/DELETE /api/admin/blueprints/*`, `POST …/validate`, `/blueprints`, `DynamicForm`, `frontend/src/api/blueprint.ts`, `ContentController::validatePayload()`, `DemoMode`

<a id="release-2-0-27"></a>

## [2.0.27] – 2026-07-19

SSO, path ACL, search, OTP workflows, counts, connector auth and feeds

### Release facts

- **Recorded source items:** 39
- **Categories:** Added, Changed, Fixed
- **Technical identifiers:** `OAuthSsoService`, `sso.defaultRole`, `GET /api/auth/sso/providers`, `/start`, `/callback`, `sso.enabled`, `AclRepository`, `PathAclService`, `data/security/acl.json`, `GET/PUT /api/admin/security/acl`

### Related incidents

- [ISS-013](docs/ISSUES.md#iss-013), [ISS-023](docs/ISSUES.md#iss-023)

<a id="release-2-0-26"></a>

## [2.0.26] – 2026-07-19

Internal WAF, structured logging, admin Logs and CI incident fixes

### Release facts

- **Recorded source items:** 11
- **Categories:** Added, Changed, Fixed, Tests/verification
- **Technical identifiers:** `FirewallMiddleware`, `/firewall`, `docs/user/FIREWALL.md`, `RequestLoggingMiddleware`, `ApplicationLogReader`, `/logs`, `GET/POST /api/admin/logs/*`, `docs/ISSUES.md`, `logs.by_severity`, `react-hooks/exhaustive-deps`

### Related incidents

- [ISS-015](docs/ISSUES.md#iss-015), [ISS-016](docs/ISSUES.md#iss-016), [ISS-017](docs/ISSUES.md#iss-017), [ISS-018](docs/ISSUES.md#iss-018), [ISS-019](docs/ISSUES.md#iss-019), [ISS-020](docs/ISSUES.md#iss-020), [ISS-021](docs/ISSUES.md#iss-021), [ISS-022](docs/ISSUES.md#iss-022)

<a id="release-2-0-25"></a>

## [2.0.25] – 2026-07-19

Admin list UX, inboxes, comment policy, navigation and PHPStan compatibility

### Release facts

- **Recorded source items:** 12
- **Categories:** Added, Changed, Fixed
- **Technical identifiers:** `AdminListPagination`, `SortableTableHeader`, `useColumnSort`, `clientListView`, `AdminInboxList`, `POST /api/admin/messages/bulk`, `POST /api/admin/comments/bulk-workflow`, `CommentPolicyResolver`, `SettingsSchema`, `comments.*`

<a id="release-2-0-24"></a>

## [2.0.24] – 2026-07-19

Post-audit security hardening and QA cleanup

### Release facts

- **Recorded source items:** 19
- **Categories:** Security, Added, Fixed, Changed, Operations/notes, Tests/verification
- **Technical identifiers:** `alltests_190726_0808.log`, `AUDIT_REPORT.md`, `POST /api/auth/reset-password`, `UserRepository`, `resetTokenHash`, `hash_equals()`, `frontend/tsconfig.json`, `frontend/tsconfig.node.json`, `frontend/eslint.config.js`, `frontend/src/vite-env.d.ts`

### Related incidents

- [ISS-011](docs/ISSUES.md#iss-011), [ISS-012](docs/ISSUES.md#iss-012)

### Evidence

- [`ff0a987`](https://github.com/techberode/paginiumcms-architecture/commit/ff0a987), [`8490387`](https://github.com/techberode/paginiumcms-architecture/commit/8490387)

<a id="release-2-0-23"></a>

## [2.0.23] – 2026-07-18

SEO preview image from Media and blog preview fix

### Release facts

- **Recorded source items:** 8
- **Categories:** Added, Documentation, Other
- **Technical identifiers:** `SeoMetadataPanel`, `MediaPickerModal`, `urlFormat: storage`, `BlogRenderer`, `contentPreviewImage`, `contentPreviewImage.ts`, `Article::getFeaturedImage()`, `APP_ENV=testing`

<a id="release-2-0-22"></a>

## [2.0.22] – 2026-07-18

Code Editor create, delete and restore

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Documentation, Tests/verification
- **Technical identifiers:** `CodeEditorManager`, `FileBackup::resolveBackupByBasename()`, `POST /file`, `DELETE /file`, `POST /restore`, `CodeEditorFileActions`, `codeEditor.ts`, `CodeEditorManagerTest::testCreateDeleteAndRestoreFile`, `CodeEditorControllerTest::testCreateDeleteAndRestoreFileFlow`

<a id="release-2-0-21"></a>

## [2.0.21] – 2026-07-18

Code Editor, 2FA UX and developer-unlock fixes

### Release facts

- **Recorded source items:** 13
- **Categories:** Added, Documentation, Tests/verification
- **Technical identifiers:** `DeveloperModeGate`, `APP_ENV=development`, `DEVELOPER_MODE`, `GET /directories`, `listAllAllowedFiles()`, `refreshCurrentUserFromStorage`, `/account/security`, `backend/bin/cli-env.php`, `.env`, `DeveloperModeGateTest`

<a id="release-2-0-20"></a>

## [2.0.20] – 2026-07-18

Content cache correctness and admin content-list improvements

### Release facts

- **Recorded source items:** 10
- **Categories:** Other, Added, Documentation
- **Technical identifiers:** `content:cache-purge [--reindex]`, `@example.com`, `APP_ENV=testing`, `ContentEditorShell`, `AdminListToolbar`, `itemsPerPage`, `/blog/{slug}`

<a id="release-2-0-19"></a>

## [2.0.19] – 2026-07-18

Admin user management and staff 2FA policy

### Release facts

- **Recorded source items:** 6
- **Categories:** Other, Added, Documentation
- **Technical identifiers:** `twoFactorSecret`, `security.requireTwoFactorStaff`, `GET /api/admin/users`, `GET /api/admin/users/{id}`, `/users`

<a id="release-2-0-18"></a>

## [2.0.18] – 2026-07-18

Cron planner and Job Queue

### Release facts

- **Recorded source items:** 8
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `Core/Scheduler/*`, `CronExpressionEvaluator`, `scheduler:run`, `worker:process`, `backup:run-schedule`, `monitoring:run-schedule`, `GET/POST/PUT/DELETE /api/admin/jobs`, `GET/POST /api/admin/backups/schedule`, `/scheduler`, `api/jobs.ts`

<a id="release-2-0-17"></a>

## [2.0.17] – 2026-07-18

Scheduled monitoring reports and log-incident scanning

### Release facts

- **Recorded source items:** 18
- **Categories:** Added, Other, Tests/verification, Documentation
- **Technical identifiers:** `Core/Monitoring/*`, `MonitoringReportScheduler`, `MonitoringReportBuilder`, `LogIncidentScanner`, `MonitoringScheduler`, `SchedulerStateStore`, `monitoring:run-schedule`, `bootstrap/app.php`, `SettingsSchema`, `getTopIpStats()`

### Related incidents

- [ISS-009](docs/ISSUES.md#iss-009)

<a id="release-2-0-16"></a>

## [2.0.16] – 2026-07-18

Shared bulk-actions platform

### Release facts

- **Recorded source items:** 28
- **Categories:** Added, Tests/verification, Documentation, Other
- **Technical identifiers:** `useBulkSelection`, `BulkActionBar`, `BulkBatchResult`, `{ processed, succeeded, failed, results[] }`, `POST/PATCH /api/{pages|articles}/bulk-*`, `POST /api/admin/trash/bulk-restore`, `POST /api/admin/comments/bulk-*`, `POST /api/admin/users/bulk-delete`, `GET .../verify`, `BulkBatchResultTest`

<a id="release-2-0-14"></a>

## [2.0.14] – 2026-07-18

Binary-safe media I/O and strict format validation

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Tests/verification, Documentation
- **Technical identifiers:** `FileWriter::writeBinary()`, `FileReader::readBinary()`, `MediaFormats`, `GET /api/media/formats`, `GET /api/media/file/{path}`, `MediaRepository::saveUpload()`, `MediaFormats::validate()`, `/api/media/file/{path}`, `/storage/...`, `/api/media/formats`

<a id="release-2-0-13"></a>

## [2.0.13] – 2026-07-18

Media preview lightbox

### Release facts

- **Recorded source items:** 5
- **Categories:** Added, Tests/verification, Documentation
- **Technical identifiers:** `MediaPreviewLightbox`, `MediaPreviewLightbox.test.tsx`, `ITERATION_26.md`, `ITERATION_25.md`, `ITERATION_BACKLOG.md`

<a id="release-2-0-12"></a>

## [2.0.12] – 2026-07-18

Folder-aware media storage and metadata sidecars

### Release facts

- **Recorded source items:** 11
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `media/folders.json`, `.paginium-folder`, `.meta.json`, `GET/POST /api/media/folders`, `POST /api/media/bulk-delete`, `allowedMimeTypes`, `maxUploadSizeKb`, `stockImageTopic`, `stockImagesEnabled`, `stock-images.json`

<a id="release-2-0-11"></a>

## [2.0.11] – 2026-07-17

SEO metadata engine

### Release facts

- **Recorded source items:** 6
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `SeoMetaBuilder`, `GET /api/seo/{type}/{slug}`, `useSeoMeta`, `PublicSiteLayout`, `SeoMetaBuilderTest`, `SeoControllerTest`, `useSeoMeta.test.ts`

<a id="release-2-0-10"></a>

## [2.0.10] – 2026-07-17

Trash management, brute-force lockout, RSS and sitemap

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `LoginAttemptTracker`, `GET /feed.xml`, `GET /sitemap.xml`, `/trash`, `build:prod`, `verify-dist-api-url.mjs`, `TrashManager.test.tsx`, `FeedGeneratorTest`, `FeedControllerTest`

<a id="release-2-0-9"></a>

## [2.0.9] – 2026-07-17

Unified API response contract

### Release facts

- **Recorded source items:** 16
- **Categories:** Added, Tests/verification, Other
- **Technical identifiers:** `JsonResponder`, `GET /api/health`, `{ success, data }`, `frontend/src/mocks/`, `VITE_MSW=true`, `content.ts`, `user.ts`, `api/index.ts`, `npm run test:msw`, `JsonResponderTest`

<a id="release-2-0-8"></a>

## [2.0.8] – 2026-07-16

RBAC and maintenance-mode middleware

### Release facts

- **Recorded source items:** 24
- **Categories:** Added, Other, Tests/verification, Documentation
- **Technical identifiers:** `PermissionMiddleware`, `content:create|edit|delete`, `media:upload|delete`, `AuthorizationManager`, `content:manage`, `media:manage`, `MaintenanceModeMiddleware`, `general.maintenanceMode`, `GET /storage/{path}`, `StorageController`

<a id="release-2-0-7"></a>

## [2.0.7] – 2026-07-16

Flat-file content index, pagination and search API

### Release facts

- **Recorded source items:** 15
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `ContentIndexService`, `data/index/content.json`, `JsonResponder`, `PaginationQuery`, `PaginationMeta`, `GET /api/pages|articles?page=&per_page=&search=&status=`, `{ data, meta }`, `per_page`, `GET /api/search?q=`, `MarkdownContentStorage`

<a id="release-2-0-6"></a>

## [2.0.6] – 2026-07-16

PHPStan Level 8 backend compliance and safe I/O helpers

### Release facts

- **Recorded source items:** 20
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `backend/app`, `backend/bootstrap`, `backend/tests`, `backend/bin`, `JsonHelper`, `FileHelper`, `RouteBootstrap::container()`, `phpstan.neon`, `backend/routes/web.php`, `ValidationTrait`

<a id="release-2-0-5"></a>

## [2.0.5] – 2026-07-15

Navigation, comments and contact modules

### Release facts

- **Recorded source items:** 12
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `GET /api/navigation`, `PUT /api/admin/navigation`, `POST /api/contact`, `GitHubService`, `SettingsSchema`, `api/navigation.ts`, `comments.ts`, `contact.ts`, `messages.ts`, `github.ts`

<a id="release-2-0-4"></a>

## [2.0.4] – 2026-07-15

Media Manager frontend

### Release facts

- **Recorded source items:** 6
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `api/media.ts`, `MediaManager`, `/media`, `MediaPlaceholder`, `MediaRepositoryTest`, `MediaControllerTest`, `media.test.ts`, `MediaManager.test.tsx`, `docs/ITERATION_8.md`

<a id="release-2-0-3"></a>

## [2.0.3] – 2026-07-15

Code policy and Code Editor foundation

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `CodePolicyEngine`, `SecurityScanner`, `CodePolicyViolationException`, `CodeEditorManager`, `FileInfo[]`, `backend/app/Http/Extensions/`, `backend/resources/views/themes/`, `SimpleLogger`, `DeveloperUnlockGate`, `api/developer.ts`

<a id="release-2-0-2"></a>

## [2.0.2] – 2026-07-15

Admin dashboard, monitoring and realtime analytics

### Release facts

- **Recorded source items:** 9
- **Categories:** Added, Tests/verification
- **Technical identifiers:** `RealtimeTracker`, `DashboardController`, `GET /api/admin/dashboard/overview`, `health.php`, `GET /api/admin/analytics/realtime`, `HealthController`, `{ success, data }`, `DashboardView`, `AnalyticsChart`, `LocksPanel`

<a id="release-2-0-1"></a>

## [2.0.1] – 2026-07-15

Settings schema for SMTP, notifications, connectors and monitoring

### Release facts

- **Recorded source items:** 27
- **Categories:** Added, Tests/verification, Documentation
- **Technical identifiers:** `SettingsSchema`, `GET /api/settings/public`, `SmtpTransport`, `NotificationFactory`, `IncidentNotifier`, `EmailAdapter`, `NtfyAdapter`, `DiscordAdapter`, `TelegramAdapter`, `WebhookAdapter`

<a id="release-2-0-0"></a>

## [2.0.0] – 2026-07-14

Flat-file core across the first five planned iterations

### Release facts

- **Recorded source items:** 41
- **Categories:** Added, Documentation, Tests/verification, Other, Fixed
- **Technical identifiers:** `main_local`, `.gitignore`, `Core/Locking`, `ContentLock`, `LockManager`, `data/locks.json`, `LockController`, `locking.php`, `GET /api/locks`, `DELETE /api/locks/{resourceId}`

### Evidence

- [`09b74ab`](https://github.com/techberode/paginiumcms-architecture/commit/09b74ab), [`12ea642`](https://github.com/techberode/paginiumcms-architecture/commit/12ea642), [`138b2e3`](https://github.com/techberode/paginiumcms-architecture/commit/138b2e3), [`b79b82d`](https://github.com/techberode/paginiumcms-architecture/commit/b79b82d), [`3b0a4d3`](https://github.com/techberode/paginiumcms-architecture/commit/3b0a4d3)

<a id="release-1-0-0"></a>

## [1.0.0] – Initial structure

Initial repository structure

### Release facts

- **Recorded source items:** 1

### Evidence

- [`45ea25c`](https://github.com/techberode/paginiumcms-architecture/commit/45ea25c), [`57c28cc`](https://github.com/techberode/paginiumcms-architecture/commit/57c28cc)
