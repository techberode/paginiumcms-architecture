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
| Post-audit security hardening + QA cleanup + roadmap It.47–49 | **2.0.24** | [below](#2024--2026-07-19) |
| Admin inbox UX, list controls, comments/nav per-content | **2.0.25** | [below](#2025--2026-07-19) |
| It.50 WAF + structured logging + admin Logy | **2.0.26** | [below](#2026--2026-07-19) |
| It.10 polish, It.11 SSO/ACL, It.41–43 search/OTP | **2.0.27** | [below](#2027--2026-07-19) |
| It.12 Blueprint + It.13 Demo sandbox v2 + PHILOSOPHY | **2.0.28** | [below](#2028--2026-07-19) |
| Session hardening, cache admin, auth/login incidents | **2.0.29** | [below](#2029--2026-07-19) |
| 2FA setup/login fixes, dev TOTP toggle, CI hotfix | **2.0.30** | [below](#2030--2026-07-19) |
| It.44 blog pagination, admin filters, link target setting | **2.0.31** | [below](#2031--2026-07-20) |
| It.44c URL sync, preview modal, reading time | **2.0.32** | [below](#2032--2026-07-20) |
| Admin deep links (settings group, audit routes, Ctrl+K catalog) | **2.0.33** | [below](#2033--2026-07-20) |
| It.52a — Dashboard KPI + overview API | **2.0.34** | [below](#2034--2026-07-20) |
| It.52b — Contact form subjects (tests + contract) | **2.0.35** | [below](#2035--2026-07-20) |
| It.52 — Dashboard v2, contact & company (complete) | **2.0.36** | [below](#2036--2026-07-20) |
| It.44d — Content API filters + server-side public blog | **2.0.37** | [below](#2037--2026-07-20) |
| It.15 — External plugins & runtime | **2.0.38** | [below](#2038--2026-07-20) |

---

## [2.0.38] – 2026-07-20

**Iteration 15** — externé doplnky mimo Core (import, registry, hooks, routes, admin UI).  
Detail: [ITERATION_15.md](docs/ITERATION_15.md).

### Added

- `PluginRegistry`, `PluginImporter`, `PluginPolicyScanner`, `PluginManager` under `Http/Extensions/`
- Flat-file registry `data/plugins.json` with flock
- Admin API: `GET/POST /api/admin/extensions`, enable/disable/uninstall
- ZIP import with `CodePolicyEngine` directory scan (422 on violation)
- Bootstrap: `bootEnabledExtensions()` + load `Http/Routes/extensions/{id}.php` for enabled plugins
- `HookManager` registered in DI
- Frontend: `extensionsApi`, `ExtensionsManager` at `/extensions`, `extensions/loader.ts`
- Tests: PHPUnit + Vitest loader

### Fixed

- `FileHelper::read()` — guard for missing/unreadable files (ISS-039 hardening)

---

## [2.0.37] – 2026-07-20

**Iteration 44d** — backend filtre a server-side verejný blog.
Detail: [ITERATION_44.md](docs/ITERATION_44.md).

### Added

- Content index filters: `tag`, `author`, `date_from` / `date_to` (`filter[…]` aj flat query)
- Paginated `GET /api/articles` meta: `tags[]`, `total_published`
- `BlogRenderer` — list/detail cez API; `PublicSiteContext` už nenačítava všetky články pri boote
- `blogSortToApiSort()` — mapovanie `newest` / `oldest` / `title` → API `sort`

### Fixed

- Content index `createdAt` pre články preferuje front matter `date` (zhoda s blog sortom)

---

## [2.0.36] – 2026-07-20

**Iteration 52 complete** — firemné údaje + mapa na kontaktnej stránke.
Detail: [ITERATION_52.md](docs/ITERATION_52.md).

### Added

- Settings group **`company`** — name, IČO, DIČ, adresa, kontakty, `mapEmbedUrl`
- **`GET /api/settings/public`** → blok `company`
- **`CompanyInfoPanel`** + **`CompanyMapEmbed`** on contact page (`PageRenderer`)
- **`isSafeMapEmbedUrl`** — len `https://www.google.com/maps/embed…`
- Vitest + PHPUnit contract tests

---

## [2.0.35] – 2026-07-20

**Iteration 52b** — Kontaktný formulár: predvolené predmety a test coverage.
Detail: [ITERATION_52.md](docs/ITERATION_52.md).

### Added

- Vitest: `contactSubjects.test.ts`, `ContactForm.test.tsx` — subjects from `settings.contact`
- PHPUnit: public settings contract asserts `contact.subjects` + `allowCustomSubject`

### Note

Funkčnosť `contact.*` settings bola zavedená v **2.0.32**; tento release formalizuje It.52b testmi a API kontraktom.

---

## [2.0.34] – 2026-07-20

**Iteration 52a** — Dashboard v2 KPI row and enriched overview API.
Detail: [ITERATION_52.md](docs/ITERATION_52.md).

### Added

- **`GET /api/admin/dashboard/overview`** — `counts` (incl. `messages_unread`) and `storage.free_space` from health check
- Dashboard KPI row: neprečítané správy → `/messages`, médiá → `/media`, voľné miesto na disku
- **`DashboardActivityPanel`** — odkaz „Celý audit trail →“ na `/audit`
- **`AdminCountsService`** — `messages_unread` for admin sidebar counts API

### Changed

- **`docs/ISSUES.md`** — ISS-037 (CI type-check hotfix)
- **`.cursorrules`** — povinné testy pred commit & push

---

## [2.0.33] – 2026-07-20

Admin anchor / deep link fixes — FE↔BE alignment.
Detail: [ADMIN_DEEP_LINKS.md](docs/architecture/ADMIN_DEEP_LINKS.md).

### Fixed

- **`AuditTrail`** — `/audit/content/:contentId` and `/audit/user/:userId` now read route params via `useParams()` (API was never called before)
- **`SettingsView`** — respects `/settings?group={key}` and legacy `location.state.group`; syncs URL when switching tabs
- **`LogsManager`** — bidirectional sync for `?severity=` (dashboard chips + browser navigation)
- Cross-module links updated to shareable query URLs (Logs, Firewall, Scheduler, Notifications dashboard)

### Added

- **`frontend/src/utils/adminDeepLinks.ts`** — path helpers + unit tests
- **`AdminRouteCatalog`** — missing sidebar routes: `/security/audit`, `/security/acl`, `/blueprints`, `/demo`, `/developer/logs`
- Vitest: `SettingsView.test.tsx`, `AuditTrail.test.tsx`

---

## [2.0.32] – 2026-07-20

Iteration 44c (FE) + It.51 preview UX + blog reading time.
Detail: [ITERATION_44.md](docs/ITERATION_44.md) · [ITERATION_51.md](docs/ITERATION_51.md).

### Added

- **It.44c:** URL sync for **MediaManager**, **CommentsManager**, **TrashManager** (`useMediaListQueryParams`, `useAdminListQueryParams`)
- **`SitePreviewModal`** — full-page preview with Navbar/Footer; scale 100 % / 75 % / 50 % / fullscreen
- Preview from **MarkdownEditor** and **PagesManager** list actions
- **`content.showReadingTime`** — toggle estimated reading time on public blog (default on)
- **`contact.subjects`** / **`contact.allowCustomSubject`** — contact form subject presets (It.52b slice)
- Dashboard activity + Flat-File structure panels (It.52a slice)
- **`ArticleTagsEditor`**, date badges (`contentDates`), Vitest coverage

### Changed

- Blog list/detail: created/updated labels, „Čítať celý článok“ CTA, optional reading time badge
- Admin list **Náhľad** uses in-app modal (loads full content via API)

### Tests

- `sitePreview.test.ts`, `readingTime.test.ts`, `useAdminListQueryParams.test.tsx`, `SitePreviewModal.test.tsx`

---

## [2.0.31] – 2026-07-20

Iteration 44 — public blog pagination, admin list URL filters, optional new-tab links.
Detail: [ITERATION_44.md](docs/ITERATION_44.md).

### Added

- **`content.blogItemsPerPage`** — verejný blog (default 6), oddelené od admin `itemsPerPage`
- **Public blog:** URL sync (`/blog?page=&tag=&sort=`), sort dropdown, prev/next článok bez návratu na zoznam
- **`ui.openLinksInNewTab`** — prepínač náhľadov / externých odkazov (default **false** = rovnaká karta)
- **`AdminListFilterBar`** + **`useAdminListQueryParams`** — URL sync admin zoznamov (`?q=&status=&sort=&page=&seo=1`)
- Utils: `blogArticles.ts`, `linkTarget.ts`, `useOpenLinksInNewTab`

### Changed

- **`BlogRenderer`** — paginácia zo settings, zdieľané sort poradie pre prev/next
- **`PagesManager`** — filtre v URL, tlačidlo „Vymazať filtre“
- Preview / media / footer / admin header — rešpektujú `openLinksInNewTab` namiesto natvrdo `_blank`

### Tests

- `blogArticles.test.ts`, `linkTarget.test.ts` (Vitest)

---

## [2.0.30] – 2026-07-19

2FA setup vs login TOTP, staff user deadlock, auth UX without hard reload.
Incident log: [ISS-030–ISS-036](docs/ISSUES.md) (extends ISS-029).

### Added

- **`TwoFactorPolicy`** + **`TWO_FACTOR_REQUIRED`** env — vypnutie TOTP v dev (`APP_ENV=development|local|testing` only; production always enforces)
- **`GET /api/auth/2fa/status`** → `setup_pending` (first-time QR setup vs login TOTP)
- Admin banner + **`ProtectedRoute`** redirect to `/account/security` during setup
- Auth events: `paginium:totp-required`, `paginium:auth-expired` (no full-page 401 redirect)

### Fixed

- **ISS-030** — QR disappears after enable; setup confused with login TOTP step
- **ISS-031** — staff user created with `twoFactorEnabled=true` but no secret (deadlock)
- **ISS-032** — `twoFactorVerifiedAt` not persisted in `UserRepository` JSON
- **ISS-033** — `client.ts` `window.location.href='/login'` on 401 (double password login)
- **ISS-029** (follow-up) — login loop when 2FA enforced on new users
- **ISS-035** — PHPStan dead `??` on `ClientIpResolver::$parts[0]` (post-2.0.29 CI hotfix)
- **ISS-036** — FE type-check after 2.0.30: `setup_pending` API unwrap, `setUser` → `updateUser`, test fixtures (`3fbc595`)

### Hotfix (2026-07-20)

- Commit **`3fbc595`** — CI green on `main` after 2.0.30 deploy; no runtime behavior change beyond correct auth state update during 2FA enable

### Changed

- `UserController` — enforced staff 2FA no longer auto-sets `twoFactorEnabled` without secret
- `TwoFactorController::enable()` — allows provisioning until first successful verify
- `AuthController::login` — `requires_two_factor` only when `twoFactorVerifiedAt !== null`
- `TwoFactorMiddleware` — bypass TOTP gate during setup (`verifiedAt === null`)
- `LoginModal` — explicit navigate after login/TOTP verify
- [DEV.md](docs/deploy/DEV.md) — `TWO_FACTOR_REQUIRED=false` troubleshooting row

### Ops — dev `.env` (optional)

```env
APP_ENV=development
TWO_FACTOR_REQUIRED=false
```

**Broken user recovery** (before deploy): reset `twoFactorEnabled` / `twoFactorSecret` / `twoFactorVerifiedAt` in user JSON, or use `/account/security` after fix deploy.

---

## [2.0.29] – 2026-07-19

Session stability (LAN/proxy), admin cache purge, auth hardening, deploy/build fixes.
Incident log: [ISS-023–ISS-029](docs/ISSUES.md).

### Added

- **`SecureSessionManager`** + **`ClientIpResolver`** — singleton session, lazy `ensureValid()`, IP binding off by default (`SESSION_STRICT=false`), trusted proxy support
- **`AuthenticationManager::touchSession()`** — session refresh on every authenticated request; wired from `AuthMiddleware`
- **Admin cache panel:** `CacheAdminService`, `CacheController`, `GET/POST /api/admin/cache/*`, `CacheManagerPanel` in Settings (`scope`: `content` | `all`)
- **CLI:** `php backend/bin/console security:clear-lockouts` — reset login lockout after failed attempts
- **`DemoLoginGuard`** — demo credentials accepted only when `DEMO_MODE=true` (production isolation)
- **Frontend auth:** `probeSession()` distinguishes expired session vs network error; keepalive every 4 min in `AuthContext`
- **`DebugEventLogger`** — no writes when `APP_ENV=testing` (PHPUnit no longer pollutes debug log)

### Fixed

- **ISS-024** — `AuthMiddleware` constructor mismatch → HTTP 500 on all auth-protected routes
- **ISS-025** — auto logout during editing/save (session lifetime, multiple managers, aggressive 401 redirect)
- **ISS-026** — documented `SESSION_USE_STRICT_MODE` (PHP ini) vs `SESSION_STRICT` (IP binding)
- **ISS-027** — debug log showed fake `POST /api/auth/login` 401 from PHPUnit (`sapi=cli`, `app_env=testing`)
- **ISS-028** — `SettingsView.tsx` broken JSX blocked `npm run build:prod`
- **ISS-029** — login loop after brief dashboard access; post-login `isAuthenticated()` guard in `AuthController`
- **ISS-023** — flaky `SearchControllerTest::testAdminSearchIncludesDraftPages` (deterministic search token + slug in front matter)
- **`MaintenanceModeMiddleware`** — allow `/api/debug/` during maintenance (client debug events)

### Changed

- `bootstrap/app.php` — `SessionManager` registered as singleton; always `SecureSessionManager`
- `bootstrap/session.php` — dev default 8 h lifetime, `session.cookie_path=/`, env comments
- `frontend/src/api/client.ts` — skip 401 → `/login` for `/api/auth/me`, locks, drafts, `requires_two_factor`
- `.env.example` — session variable documentation; [DEV.md](docs/deploy/DEV.md) troubleshooting table extended

### Ops (recommended LAN `.env` on PHP host)

```env
SESSION_LIFETIME=28800
SESSION_STRICT=false
SESSION_USE_STRICT_MODE=true
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

After deploy: restart PHP, clear browser cookies, re-login.

---

## [Unreleased]

*(Next: It.14 — Code policy engine)*

---

## [2.0.28] – 2026-07-19

It.12 Blueprint engine, It.13 Demo sandbox v2, project philosophy docs.

### Added (It.12 — Blueprint engine)

- **`BlueprintRepository`** — flat-file `data/blueprints/{type}.json`; built-in `page` + `article` defaults
- **`DynamicValidator`** — blueprint field rules → shared `Validator`
- **`GET/PUT/DELETE /api/admin/blueprints/*`**, `POST …/validate`
- Admin **`/blueprints`**, `DynamicForm` preview, `frontend/src/api/blueprint.ts`
- **`ContentController::validatePayload()`** → `DynamicValidator` on save for `page` / `article`

### Added (It.13 v1 — Demo infra)

- **`DemoMode`** + **`DemoStorageService`** — isolated `storage/app/demo/`, seed reset
- **`GET /api/admin/demo/status`**, **`POST /api/admin/demo/reset`** (SUPER_ADMIN)
- Public settings **`demo.enabled`**; admin banner + **`/demo`** manager
- **`DemoFixtures::seedFiles()`** — demo pages/articles; MOCK comments/messages

### Added (It.13 v2 — Live demo sandbox)

- **`DEMO_MODE`** switches **`FileValidator`** base path → entire CMS reads/writes `storage/app/demo/`
- **Full snapshot seed** — pages, articles, content index, settings, navigation, demo admin user
- **`DemoResetScheduler`** + CLI **`demo:reset-if-due`** (`DEMO_AUTO_RESET_MINUTES`)
- **`SESSION_LIFETIME`** from `.env` (long session on demo instance)
- Public **`demo.credentials`**, **`demo.url`**; login page “Vyplniť demo údaje”
- Footer marketing link → **`demo.paginiumcms.com`** (hidden on demo instance)
- Bootstrap **`ensureSeeded()`** on first boot; `.env` fallback root or `backend/.env`

### Docs

- **[PHILOSOPHY.md](docs/PHILOSOPHY.md)** — open source, no fees, why project exists
- [ITERATION_12.md](docs/ITERATION_12.md), [ITERATION_13.md](docs/ITERATION_13.md) updated

---

## [2.0.27] – 2026-07-19

Enterprise security (It.11), advanced search (It.43), workflow OTP (It.41), admin sidebar counts (It.42), connector auth (It.47), and RSS/sitemap polish (It.10).

### Added (It.11 — SSO, path ACL, security audit)

- **`OAuthSsoService`** — GitHub + generic OAuth2 (curl); auto-provision users with `sso.defaultRole`
- **`GET /api/auth/sso/providers`**, `/start`, `/callback`; public `sso.enabled` in settings
- Settings group **`sso`** (GitHub + generic OAuth fields)
- **`AclRepository`** + **`PathAclService`** — flat-file `data/security/acl.json`, glob path rules on RBAC
- **`GET/PUT /api/admin/security/acl`**; admin **`/security/acl`**
- **`SecurityAuditStore`** — `data/security/audit_events.json`; failed login, permission denied, settings/ACL/SSO events
- **`GET /api/admin/security/audit`** + CSV export; admin **`/security/audit`**
- Frontend: `frontend/src/api/security.ts`, `LoginModal` SSO buttons, `SecurityAuditManager`, `AclManager`
- Docs: [ITERATION_11.md](docs/ITERATION_11.md)

### Added

- **It.43 Advanced search:** `AdvancedSearchService`, `GET /api/search?scope=admin|public&types=…`
- **Admin command palette:** `Ctrl+K` / `Cmd+K` — pages, articles, media, admin modules + recent jumps
- **`TestStorageCleaner`** + `backend/bin/test-artifacts.php` — end-of-suite cleanup (generic test artifacts only)
- **`settings.testing.json`** — PHPUnit HTTP tests isolated from production `settings.json` (SMTP safe)
- **`run-all-tests.zsh`:** live output, post-step progress, step 12 cleanup, PHPStan error count fix
- Docs: [ITERATION_43.md](docs/ITERATION_43.md), expanded [TESTING.md](docs/developer/TESTING.md)

### Added (It.42 — admin sidebar counts)

- `AdminCountsService` + `GET /api/admin/counts` (role-aware aggregates).
- Settings `ui.showListCounts` toggle; public settings expose `ui.showListCounts`.
- `useAdminCounts` hook; `AdminSidebar` badges from backend counts.

### Added (It.41 — registration email OTP)

- Settings group `workflows`: registration/comment/publish OTP toggles, TTL, max attempts.
- `OtpChallengeStore` + `OtpWorkflowService` — flat-file OTP challenges.
- `POST /api/auth/register/verify-otp`, `POST /api/auth/register/resend-otp`; register returns `202` + `requires_otp` when enabled.
- `POST /api/admin/workflows/otp/verify`, `POST /api/admin/workflows/otp/resend` — editor comment approve + content publish OTP.
- Comment approve / content save-to-publish return `202` + `requires_otp` when workflow toggles are on.
- Public settings: `workflows.registrationOtpEnabled`, `general.allowRegistration`.
- Frontend: `RegisterModal`, `OtpConfirmModal`, `CommentsManager`, `MarkdownEditor` publish OTP flow.

### Added (It.47 — notification connector auth)

- Settings `connectors`: `ntfyAuthMode`, `ntfyAccessToken`, `ntfyUsername`, `ntfyPassword`, `webhookAuthHeader`.
- `NtfyAdapter` Bearer/Basic auth headers; `WebhookAdapter` configurable secret header.
- `POST /api/admin/notifications/test-connector` — credential validation + delivery test.
- Connector overview fields: `configured`, `authenticated`, `auth_mode`.
- Admin `/notifications`: Auth OK / Chýba auth badges, **Verify auth** button.

### Changed

- `ContentIndexService::search()` — optional `$publishedOnly` for admin draft search
- `SiteSearchModal` API client — explicit `scope=public`

### Added (It.10 polish — RSS/sitemap discoverability)

- **`GET /robots.txt`** — `Sitemap:` directive when feeds enabled
- **`ContentCacheService`** — cached RSS/sitemap/robots XML (TTL 300s); invalidates on content publish
- **`RobotsTxtGenerator`** + public `<link rel="sitemap">` in `PublicSiteLayout`
- Postman smoke folder **Public Feeds**; nginx/vite proxy for `/robots.txt`

### Fixed

- **ISS-023** — flaky `SearchControllerTest::testAdminSearchIncludesDraftPages` (deterministic token + slug in front matter) — fixed in **2.0.29**
- **ISS-013** — private ntfy topics no longer fail silently when token/Basic auth is required.
- Test suite: `TestStorageCleaner` index format, `ContentDiagnoseCommandTest` `--fix`, PHPStan in `test-artifacts.php`

---

## [2.0.26] – 2026-07-19

Interný WAF (It.50), production HTTP logging, admin Logy modul, CI incident docs (ISS-015–019).

### Added

- **WAF (It.50):** `FirewallMiddleware`, 5 built-in scenárov, jail/permanent ban, admin `/firewall`,
  settings skupina `firewall`, `docs/user/FIREWALL.md`.
- **Structured logging:** `RequestLoggingMiddleware` — každý endpoint s timestamp + IP;
  `ApplicationLogReader` agreguje app/audit/event/user logy.
- **Admin Logy:** `/logs`, dashboard panel severity (24 h), `GET/POST /api/admin/logs/*`.
- **Settings:** skupina `logging` (requestLogging, minSeverity, retentionDays, slowRequestMs).
- **CI docs:** ISS-015–019 v `docs/ISSUES.md` (PHPStan, PHPUnit, TypeScript CI fixes).

### Changed

- Dashboard overview obsahuje `logs.by_severity` pre panel Logy.

### Fixed

- CI ESLint: `react-hooks/exhaustive-deps` — hook deps v `useToast`, CodeEditor, AuditTrail, MediaManager, … (ISS-020).
- CI PHPStan: redundantné `is_array()` v `ApplicationLogReader` (ISS-021).
- CI Vitest: `MediaManager.test.tsx` — role-based asercie + stabilný `useToast` mock (ISS-022).

### Tests

- Firewall + LogController PHPUnit; PHPStan L8; frontend type-check.
- `MediaManager.test.tsx` — 5/5 (ISS-022).

---

## [2.0.25] – 2026-07-19

Admin list UX (It.42+), Gmail-style inbox pre Správy/Komentáre, per-article comment policy,
viacúrovňové menu a PHP 8.4 kompatibilita v PHPStan.

### Added

- **Admin list platform:** `AdminListPagination`, `SortableTableHeader`, `useColumnSort`,
  `clientListView` — stránkovanie a zoradenie v hlavičkách (Media, Články, Backups, Kôš).
- **Inbox UI:** `AdminInboxList` — Gmail-like zoznam so zebra riadkami, rozklikom, bulk akciami
  (Správy, Komentáre).
- **Správy:** priorita, workflow (`isRead` / `isProcessed` / `isArchived`), bulk API
  `POST /api/admin/messages/bulk`.
- **Komentáre:** `isRead` / `isArchived`, bulk workflow `POST /api/admin/comments/bulk-workflow`.
- **Kôš:** bulk purge/backup, „Vysypať kôš“, download zálohy.
- **Per-article komentáre:** panel v editore článku + `CommentPolicyResolver` (globálne + override).
- **Menu:** admin editor s inline editáciou a submenu do 3 úrovní; verejný `Navbar` s dropdownom.
- **Docs:** [CONTENT_COMMENTS_NAV.md](docs/CONTENT_COMMENTS_NAV.md), [ITERATION_50.md](docs/ITERATION_50.md) (WAF spec).

### Changed

- Globálne nastavenia komentárov — slovenské labely v `SettingsSchema`; verejný výrez `comments.*`
  v `GET /api/settings/public`.
- PHPStan `phpVersion` zladený s `composer.json` minimum (**8.4**, bolo 8.5).

### Fixed

- PHPStan L8: match arms v bulk controlleroch, `fopen()` guard v `TrashController::downloadBackup`.
- PHPUnit izolácia trash store + resilientné načítanie položiek kôša.

---

## [2.0.24] – 2026-07-19

Post-audit hardening (commits `ff0a987`, `8490387`) plus test/lint hygiene and
planned iterations **It.47–It.49**. Full test log `alltests_190726_0808.log`: **10/10 OK**.

> **Audit notes:** `AUDIT_REPORT.md` v koreni repa je **lokálny / gitignored** — slúži
> len na prehľad auditov u teba na disku. Verejné sledovanie nálezov a stavu opráv:
> tento CHANGELOG + [docs/ISSUES.md](docs/ISSUES.md).

### Security

- **Account enumeration on password reset (S1).** `POST /api/auth/reset-password`
  always returns the same generic response regardless of whether the account exists.
  The reset token is never returned outside `development`/`testing`.
- **Reset tokens stored in plaintext (S4).** `UserRepository` stores SHA-256 hash
  (`resetTokenHash`) and verifies with `hash_equals()`.
- **Stored XSS via SVG media (S2).** SVG/HTML/XML served as `attachment` with
  `nosniff` and sandbox CSP; raster images stay `inline`.
- **Content Security Policy (S5).** Removed `'unsafe-inline'` from `script-src`;
  `style-src 'unsafe-inline'` retained for React inline styles.

### Added

- Frontend type-safety and linting toolchain:
  `frontend/tsconfig.json`, `frontend/tsconfig.node.json`,
  `frontend/eslint.config.js`, `frontend/src/vite-env.d.ts`.
- CI runs `npm run type-check` and `npm run lint` (`.github/workflows/ci.yml`).
- Shared Vitest router wrapper: `frontend/src/test/renderWithRouter.tsx` (React Router
  v7 `future` flags).
- Roadmap iterations:
  - **[It.47](docs/ITERATION_47.md)** — notification connector auth (ntfy Bearer/Basic, test per channel)
  - **[It.48](docs/ITERATION_48.md)** — PHP frontmatter templates, JSON/INI metadata, static HTML, dynamic/static web toggle
  - **[It.49](docs/ITERATION_49.md)** — unified cache layer (file + Redis, hosting-aware `auto` mode)

### Fixed

- **CI backend suite (12 order-dependent failures on clean checkout).**
  `ContentRepository` returns an empty list when a content directory (e.g. `content/blog/`)
  does not exist yet instead of HTTP 500 from `FileNotFoundException`.
- **38 TypeScript errors** surfaced by new `type-check` (env types, `useApi`, preview paths, etc.).
- **All ESLint errors** cleared (0 errors); warnings capped (see Changed).
- **Vitest stderr noise:** `act(...)` and React Router future-flag warnings in
  `DeveloperUnlockGate.test.tsx` and `MediaManager.test.tsx` — replaced `fireEvent`
  with `userEvent` + `waitFor`.

### Changed

- `npm run lint` uses `--max-warnings 65` — new warnings fail CI (baseline frozen
  at audit follow-up; see ISS-011 in [ISSUES.md](docs/ISSUES.md)).
- [ITERATION_45.md](docs/ITERATION_45.md) — Redis driver detail; full product layer in It.49.
- [ITERATION_BACKLOG.md](docs/ITERATION_BACKLOG.md) and [ROADMAP.md](docs/ROADMAP.md) — It.47–49 added.

### Notes

- **CSRF enforcement (S3) deferred** — single-use token vs SPA token reuse; `SameSite=Lax`
  mitigates; tracked as ISS-012.
- **ESLint warnings (65)** — mostly `@typescript-eslint/no-explicit-any` and
  `react-hooks/exhaustive-deps`; reduce incrementally from API layer (`client.ts`, `useApi.ts`).

### Verification

- Backend: PHPStan L8 clean, PHPUnit 569 passing (15 skips).
- Frontend: Vitest 130, MSW 4, `type-check` OK, lint 0 errors / ≤65 warnings, build OK.

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
