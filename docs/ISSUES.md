---
title: Known Incidents and Fixes
description: Canonical PaginiumCMS incident register with causes, resolutions, verification evidence, and stable links
icon: material/alert-circle-check
---

# PaginiumCMS – Known Incidents and Fixes

> **Last updated:** 13 August 2026 · register **ISS-001–ISS-141** · **`v2.1.0-beta.40`** hotfix (System Update deploy)

This is the canonical public register of production, integration, security, operations, and CI incidents found during PaginiumCMS development. Every incident number in the overview is a stable link to its record.

> **Link stability:** Explicit anchors use `#iss-001` through `#iss-123`. Titles may be edited without breaking changelog, release-note, commit, or GitHub Issue references.

> **Edition note:** The English branch provides a complete operational synopsis for all 123 records, including status, evidence, commands, commits, releases, and cross-links. The Slovak branch remains the verbatim detailed source record and is linked from every incident.

> **Audit report:** The root `AUDIT_REPORT.md` may be a local/gitignored working document. Public remediation status belongs here and in [CHANGELOG.md](../CHANGELOG.md).

<a id="overview"></a>

## Overview

| ID | Symptom | Severity | Status |
|---|---|---|---|
| [ISS-001](#iss-001) | Debug client-event endpoint returned 404 | Low (console noise) | ✅ Fixed |
| [ISS-002](#iss-002) | GET /api/pages returned 500 on the dashboard | High | ✅ Intermittent — diagnose + hardening |
| [ISS-003](#iss-003) | Phantom or duplicate users appeared repeatedly | Medium | ✅ Backend hardening |
| [ISS-004](#iss-004) | navigation.json.backup.* files accumulated | Low | ✅ Backup retention |
| [ISS-005](#iss-005) | Vitest worker crashed or hung | Medium (CI) | ✅ Fixed |
| [ISS-006](#iss-006) | PHPStan reported 15 errors | Medium (CI) | ✅ Fixed |
| [ISS-007](#iss-007) | Dashboard displayed the wrong user count | Low | ✅ Fixed |
| [ISS-008](#iss-008) | Password fields were served over HTTP | Info | ⏳ HTTPS in production |
| [ISS-009](#iss-009) | Settings crashed with n.max is not a function | High | ✅ Fixed |
| [ISS-010](#iss-010) | Vitest stderr contained act() and router future-flag warnings | Low (CI noise) | ✅ Fixed (2.0.24) |
| [ISS-011](#iss-011) | ESLint warning baseline and technical debt | Low (technical debt) | ⏳ 57/65 baseline, gradual cleanup |
| [ISS-012](#iss-012) | CSRF middleware was not wired into mutating routes | Medium | ✅ Fixed — `CsrfMiddleware` (synchronizer-token) |
| [ISS-013](#iss-013) | ntfy private topics failed without authentication | Medium | ✅ It.47 (Bearer/Basic + test-connector) |
| [ISS-014](#iss-014) | Development CORS wildcards could remain active with an incorrect APP_ENV | Low | ⏳ Verify deployment |
| [ISS-015](#iss-015) | PHPUnit suffered 429, 503, and OTP persistence failures | Medium (CI) | ✅ Fixed (2.0.25) |
| [ISS-016](#iss-016) | PHPStan phpVersion did not match composer.json | Medium (CI) | ✅ Fixed (2.0.25) |
| [ISS-017](#iss-017) | PHPStan reported match.alwaysTrue in bulk controllers | Medium (CI) | ✅ Fixed (2.0.25) |
| [ISS-018](#iss-018) | TrashController passed a possible false fopen result as a resource | Medium (CI) | ✅ Fixed (2.0.25) |
| [ISS-019](#iss-019) | Strict TypeScript type-check failures | Medium (CI) | ✅ Fixed (2.0.25) |
| [ISS-020](#iss-020) | ESLint exceeded the 65-warning CI limit | Medium (CI) | ✅ Fixed (2.0.26) |
| [ISS-021](#iss-021) | Redundant is_array checks failed PHPStan | Medium (CI) | ✅ Fixed (2.0.26) |
| [ISS-022](#iss-022) | MediaManager Vitest assertions were brittle | Medium (CI) | ✅ Fixed (2.0.26) |
| [ISS-023](#iss-023) | Admin draft search test was flaky | Medium (CI) | ✅ Fixed (2.0.29) |
| [ISS-024](#iss-024) | AuthMiddleware caused HTTP 500 on protected routes | Critical | ✅ Fixed (2.0.29) |
| [ISS-025](#iss-025) | Users were logged out while editing or saving | High | ✅ Fixed (2.0.29) |
| [ISS-026](#iss-026) | SESSION_USE_STRICT_MODE was confused with SESSION_STRICT | Medium (ops) | ✅ Documented (2.0.29) |
| [ISS-027](#iss-027) | PHPUnit produced false login-401 debug records | Low (diagnostics) | ✅ Fixed (2.0.29) |
| [ISS-028](#iss-028) | Production frontend build failed on malformed JSX | High (deploy) | ✅ Fixed (2.0.29) |
| [ISS-029](#iss-029) | Login briefly succeeded and then returned to /login | High | ✅ 2.0.29 session; **2.0.30** 2FA loop |
| [ISS-030](#iss-030) | 2FA setup QR disappeared and redirected to TOTP login | High | ✅ Fixed (2.0.30) |
| [ISS-031](#iss-031) | New staff users had twoFactorEnabled without a secret | Critical | ✅ Fixed (2.0.30) |
| [ISS-032](#iss-032) | twoFactorVerifiedAt was not persisted to user JSON | High | ✅ Fixed (2.0.30) |
| [ISS-033](#iss-033) | Frontend 401 interceptor caused a full-page double login | High | ✅ Fixed (2.0.30) |
| [ISS-034](#iss-034) | Development environment lacked a TOTP policy switch | Medium (DX) | ✅ Fixed (2.0.30) |
| [ISS-035](#iss-035) | ClientIpResolver contained a dead null-coalescing fallback | Low (CI) | ✅ Fixed (2.0.29 hotfix) |
| [ISS-036](#iss-036) | 2FA setup_pending and setUser broke frontend type-checking | Medium (CI) | ✅ Fixed (2.0.30 hotfix `3fbc595`) |
| [ISS-037](#iss-037) | Unused React import failed frontend type-checking | Low (CI) | ✅ Fixed (hotfix `64cc894`) |
| [ISS-038](#iss-038) | Content index tag, author, and date filters failed PHPUnit | Medium (CI) | ✅ Fixed (`54b013c`) |
| [ISS-039](#iss-039) | LogWriter tests failed on virtual files and corrupt JSON | Medium (CI) | ✅ Fixed (`54b013c`) |
| [ISS-040](#iss-040) | Corrupt access log caused JsonException and global API 500 | Critical (prod) | ✅ Fixed (`743e922`) |
| [ISS-041](#iss-041) | Unused refetch variable failed PagesManager type-checking | Low (CI) | ✅ Fixed (hotfix 2.0.40) |
| [ISS-042](#iss-042) | First login attempt failed while the second succeeded | High (auth UX) | ✅ Fixed (**2.0.43**) |
| [ISS-043](#iss-043) | editorToolbar test used global screen against multiple renders | Low (CI) | ✅ Fixed (2.0.42 It.54) |
| [ISS-044](#iss-044) | services.php parse error caused all API requests to fail | Critical | ✅ Fixed (**2.0.45**) |
| [ISS-045](#iss-045) | LocaleScaffoldService projectRoot failed PHPStan and PHPUnit | Medium (CI) | ✅ Fixed (**2.0.45**) |
| [ISS-046](#iss-046) | Audit events were stored under the app category | High (audit) | ✅ Fixed (**2.0.46**) |
| [ISS-047](#iss-047) | Dashboard activity overview was empty | High (admin UX) | ✅ Fixed (**2.0.46**) |
| [ISS-048](#iss-048) | Audit messages were unreadable or used the wrong locale | Medium (audit UX) | ✅ Fixed (**2.0.46**) |
| [ISS-049](#iss-049) | Daily log file 2026-07-21.json was corrupt | Medium (ops) | ✅ Fixed (**2.0.46**) |
| [ISS-050](#iss-050) | Logs section used the wrong reader path and appeared empty | High (admin UX) | ✅ Fixed (**2.0.46**) |
| — | Login background supported URL input only; upload/media selection was later added. | Medium (admin UX) | ✅ Fixed (**2.0.46**) |
| [ISS-051](#iss-051) | DevTokenGenerator exception crashed production boot and CLI | Critical (boot/CLI) | ✅ Fixed (security_fix hotfix) |
| [ISS-052](#iss-052) | TOTP, SMTP, SSO, and ntfy secrets were stored in plaintext | Medium (security) | ✅ Fixed — `EncryptionService` + `APP_KEY` |
| [ISS-053](#iss-053) | Control characters enabled log and CSV injection | Low–Medium (security) | ✅ Fixed — `LogSanitizer` |
| [ISS-054](#iss-054) | Admin-configurable outbound URLs enabled SSRF paths | Low–Medium (security) | ✅ Fixed — `OutboundUrlGuard` |
| [ISS-055](#iss-055) | Path ACL existed but was not enforced for content and media | Medium (security) | ✅ Fixed — `ContentPathAclGuard` |
| [ISS-056](#iss-056) | WAF scanned URI and headers but not POST or JSON bodies | Medium (security) | ✅ Fixed — body scan + editor exempt |
| [ISS-057](#iss-057) | UserRepository lookups scanned every JSON file in O(n) | Low (performance) | ✅ Fixed — `UserIndexService` + `data/index/users.json` |
| [ISS-058](#iss-058) | OTP lacked a dedicated limiter and resend reset attempts | Medium (security) | ✅ Fixed — `Otp*RateLimitMiddleware` + `resend_count` |
| [ISS-059](#iss-059) | Vitest used useI18n without I18nProvider | Low (CI) | ✅ Fixed — `renderWithProviders` (**2.0.47**) |
| [ISS-060](#iss-060) | English settings catalogue contained Slovak workflow labels | Medium (i18n UX) | ✅ Fixed (**2.0.47** / `f0a885c`) |
| [ISS-061](#iss-061) | Audit messages remained Slovak in the English admin locale | Medium (i18n UX) | ✅ Fixed (**2.0.49**) |
| [ISS-062](#iss-062) | Public website contained hard-coded Slovak strings | Medium (i18n UX) | ✅ Fixed (**2.0.50**) |
| [ISS-063](#iss-063) | Invalid date values crashed admin and public views | High (prod crash) | ✅ **2.0.51** |
| [ISS-064](#iss-064) | DEFAULT_LOCALE was not exported from i18n/index.ts | Low (CI) | ✅ **2.0.51** |
| [ISS-065](#iss-065) | Admin logs were two hours behind because PHP used UTC | Medium (ops) | ✅ **2.0.51** |
| [ISS-066](#iss-066) | CronExpressionEvaluator same-minute and DST tests failed | Low (CI) | ✅ **2.0.51** |
| [ISS-067](#iss-067) | LocaleMiddleware test mock broke after timezone middleware | Low (CI) | ✅ **2.0.51** |
| [ISS-068](#iss-068) | Expected Code Policy rejection was logged as ERROR with stack trace | Medium (logging) | ✅ **2.0.51** |
| [ISS-069](#iss-069) | Timezone setting was only a free-text field | Medium (admin UX) | ✅ **2.0.51** |
| [ISS-070](#iss-070) | Settings lacked a daylight-saving-time switch | Medium (ops) | ✅ **2.0.51** |
| [ISS-071](#iss-071) | Logs lacked bulk actions, delete-all, and pagination | Medium (admin UX) | ✅ Fixed · **2.0.51** |
| [ISS-072](#iss-072) | Security audit endpoint returned 403 for ADMIN | Medium (regression) | ✅ Fixed · **2.0.52** |
| [ISS-073](#iss-073) | Login tests returned 429 instead of 401 because lockouts persisted | Medium (CI) | ✅ Fixed · **2.0.52** |
| [ISS-074](#iss-074) | Access-control and branding changes introduced ten PHPStan errors | Medium (CI) | ✅ Fixed · **2.0.52** |
| [ISS-075](#iss-075) | PHPUnit crashed on duplicate HelloWidget Hooks class | Medium (CI) | ✅ Fixed · **2.0.54** |
| [ISS-076](#iss-076) | passwordConfirm rollout triggered a cascade of 21 PHPUnit failures | Medium (CI) | ✅ Fixed · **2.0.56** |
| [ISS-077](#iss-077) | Audit-trail CSV export bypassed LogSanitizer | Medium (security) | ✅ Fixed · **2.1.0-beta.2** |
| [ISS-078](#iss-078) | react-router-dom advisories appeared after beta.2 | Medium (dependency) | ✅ Fixed · **2.1.0-beta.3** |
| [ISS-079](#iss-079) | Blog profile rejected existing fenced code blocks on save | High (admin UX) | ✅ Fixed · **2.1.0-beta.5** |
| [ISS-080](#iss-080) | ContentMetaController called a missing getGroup method | Medium (CI) | ✅ Fixed · **2.1.0-beta.4** |
| [ISS-081](#iss-081) | Partial @tiptap dependency update caused peer conflict and CI failure | Medium (CI / deps) | ✅ Fixed · **2.1.0-beta.4** |
| [ISS-082](#iss-082) | symfony/yaml 8 upgrade was incompatible with the current constraint | Low (technical debt) | ⏳ Deferred — major migrácia |
| [ISS-083](#iss-083) | ESLint 10 required a breaking flat-config migration | Low (technical debt) | ⏳ Deferred — samostatný upgrade |
| [ISS-084](#iss-084) | Chrome sessions expired after roughly 24 minutes and caused 401 cascades | High (auth UX) | ✅ Fixed · **2.1.0-beta.5** |
| [ISS-085](#iss-085) | Rich navigation icon rendered as an empty frame and hid description | Medium (admin/public UX) | ✅ Fixed · **2.1.0-beta.5** |
| [ISS-086](#iss-086) | Stored XSS survived strip_tags through dangerous attributes and schemes | **Critical (security)** | ✅ Fixed · **2.1.0-beta.6** |
| [ISS-087](#iss-087) | LAN frontend deploy script contained hard-coded host, user, and port | Medium (ops / hygiene) | ✅ Fixed · **2.1.0-beta.6** |
| [ISS-088](#iss-088) | Backup import was vulnerable to Zip-Slip through extractTo | Medium (security) | ✅ Fixed · **2.1.0-beta.6** |
| [ISS-089](#iss-089) | React Router RSC-only advisory was accepted as not reachable in the SPA | Low (dependency) | ✅ **2.1.0-beta.29** · `react-router-dom@7.18.2` (GHSA-qwww-vcr4-c8h2) |
| [ISS-090](#iss-090) | eslint latest and npm audit fix caused dependency resolution failure | Low (CI/deps) | ✅ Fixed · **2.1.0-beta.7** |
| [ISS-091](#iss-091) | React Router override and useOptimistic caused fourteen Vitest failures | Medium (CI) | ✅ Fixed · **2.1.0-beta.7** |
| [ISS-092](#iss-092) | Deploy script mixed local environment assumptions with invalid :? syntax | Low (ops) | ✅ Fixed · **2.1.0-beta.7** |
| [ISS-093](#iss-093) | brace-expansion override broke ESLint with expand is not a function | Medium (CI) | ✅ Fixed · odstránený override |
| [ISS-094](#iss-094) | Production job scheduler run endpoint returned HTTP 500 | High (prod) | ✅ Fixed · **It.62** (`f7a73f1`) |
| [ISS-095](#iss-095) | Maintenance heroImageUrl rejected valid /storage/ paths | Medium (admin UX) | ✅ Fixed · **main `88cbe31`** |
| [ISS-096](#iss-096) | Temporary 502 occurred immediately after restarting the PHP container | Low (ops) | ℹ️ Informational — počkať 5–10 s |
| [ISS-097](#iss-097) | Newsletter subscribers had no administration interface | Medium | ✅ Fixed · **It.61** |
| [ISS-098](#iss-098) | Demo login returned empty 401 responses because of CORS and APP_URL | **High (demo)** | ✅ Fixed · **SameOriginCors** + `.env` |
| [ISS-099](#iss-099) | Demo reset CLI lacked permission to update plugins.json | Medium (demo ops) | ℹ️ Ops — storage `chown user:www-data`, dirs `2775` |
| [ISS-100](#iss-100) | Public settings exposed the demo password | Medium (audit) | ✅ **`v2.1.0-beta.11`** — quick-login, no password in GET |
| [ISS-101](#iss-101) | Editor crashed because capabilities was not normalized to an array | High (demo/admin) | ✅ **`v2.1.0-beta.11`** — normalize API profile shape |
| [ISS-102](#iss-102) | Demo API returned HTTP 500 because the demo data tree could not be created | **High (demo)** | ✅ Ops — storage bootstrap (2026-07-27) |
| [ISS-103](#iss-103) | Local DEMO_MODE polluted PHPUnit OTP and 2FA tests | Medium (dev/CI) | ✅ **`v2.1.0-beta.12`** — test bootstrap izolácia |
| [ISS-104](#iss-104) | ADMIN could bypass SUPER_ADMIN through the system-deploy jobs API | Medium (audit) | ✅ **`v2.1.0-beta.15`** |
| [ISS-105](#iss-105) | GeoIP lookup used cleartext HTTP without OutboundUrlGuard | Low (audit) | ✅ **`v2.1.0-beta.15`** |
| [ISS-106](#iss-106) | DEMO_MODE could be enabled in production without failing closed | Low (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-107](#iss-107) | Maintenance newsletter subscription lacked honeypot and dedicated rate limit | Low (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-108](#iss-108) | GitHubService curl calls bypassed OutboundUrlGuard | Info (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-109](#iss-109) | Newsletter footer call-to-action was too large | Low (UX) | ✅ **`v2.1.0-beta.18`** |
| [ISS-110](#iss-110) | Production SEO endpoint returned 500 due to cache shape collision | **High (prod)** | ✅ **`v2.1.0-beta.21`** |
| [ISS-111](#iss-111) | LoggerTest and PHPStan regressed after testing-environment log suppression | Medium (CI/tests) | ✅ **`v2.1.0-beta.21`** |
| [ISS-112](#iss-112) | Lock badge displayed activity more than 56 years ago | Low (admin UX) | ✅ fixed lokálne — **next release** |
| [ISS-113](#iss-113) | Static SPA responses lacked security headers | Medium (audit) | ✅ nginx snippet + prod/demo template |
| [ISS-114](#iss-114) | CSRF exemption prefix lacked a slash boundary | Medium (audit) | ✅ `CsrfMiddleware::isExempt()` |
| [ISS-115](#iss-115) | expose_php disclosed the PHP version | Low (audit) | ✅ `docker/php/php.ini` |
| [ISS-116](#iss-116) | TRUSTED_PROXIES default contained a hard-coded LAN address | Low (audit) | ✅ default `127.0.0.1,::1` + `.env` |
| [ISS-117](#iss-117) | React Router RSC advisory was not applicable to the SPA profile | Low (dependency) | ✅ **2.1.0-beta.29** · see [ISS-089](#iss-089) |
| [ISS-118](#iss-118) | security.txt was missing or swallowed by SPA fallback | Low (audit) | ✅ `frontend/public/.well-known/` + nginx |
| [ISS-119](#iss-119) | Docker stack did not restart after host reboot | Medium (ops) | ✅ `restart: unless-stopped` v prod compose |
| [ISS-120](#iss-120) | CI PHPUnit output exposed TOTP and 2FA secrets in GitHub job logs | Medium (security / CI) | ✅ sanitize wrapper + verify |
| [ISS-121](#iss-121) | Invalid settings group shapes were silently dropped during normalization | Medium (data integrity) | ✅ **2.1.0-beta.28** · It.68 fail-closed validation |
| [ISS-122](#iss-122) | Storage read path did not enforce base-path containment (symlink escape) | Medium (security) | ✅ **2.1.0-beta.28** · It.68 `LocalFlatFileStorage` |
| [ISS-123](#iss-123) | Corrupt `settings.testing.json` leaked between HTTP PHPUnit tests | Low (tests) | ✅ **2.1.0-beta.28** · `Http/TestCase` reset |
| [ISS-124](#iss-124) | CSP retains `style-src 'unsafe-inline'` for React inline styles | Low (documented residual) | ℹ️ It.67 inventory · no script unsafe-inline |
| [ISS-125](#iss-125) | DEMO_MODE leaked into HTTP PHPUnit after demo tests (4 failures post-beta.27) | Medium (CI) | ✅ **2.1.0-beta.28** · test env hardening |
| [ISS-126](#iss-126) | Post-beta.27 CI regressions (storage path, void mock, API barrel, demo disk metric) | Medium (CI / demo UX) | ✅ **2.1.0-beta.28** · hotfix on `main` |
| [ISS-127](#iss-127) | It.71 Performance Guard DI incomplete — 233 PHPUnit errors, console fatal | High (CI / bootstrap) | ✅ **2.1.0-beta.28** · explicit DI + ACL default |
| [ISS-128](#iss-128) | Engine Performance Guard settings could not be saved (`float` field / Zod) | Medium (admin UX) | ✅ **2.1.0-beta.28** · float input + numeric Zod |
| [ISS-129](#iss-129) | FileDriver emitted PHP warning on read-only cache dir (CI PHPUnit) | Low (CI / cache) | ✅ **2.1.0-beta.28** · writable guard + silent write fail |
| [ISS-130](#iss-130) | Newsletter unreadable in light mode (admin + public modal) | Medium (UX) | ✅ **2.1.0-beta.28** · theme token fix |
| [ISS-131](#iss-131) | PHP_CodeSniffer blame-report command injection (CVE-2026-67434) | High (dev dep / CI) | ✅ **2.1.0-beta.28** · PHPCS 4.0.4 |
| [ISS-132](#iss-132) | `analyticsChartData.ts` wrong import depth (TS2307) after Phase B charts | Low (CI) | ✅ **2.1.0-beta.28** · import path fix |
| [ISS-133](#iss-133) | BackToTopButton Vitest missing `I18nProvider` after admin wiring | Low (CI) | ✅ **2.1.0-beta.28** · `renderWithProviders` |
| [ISS-134](#iss-134) | HTTP PHPUnit flaky auth/system-update (401/403/404) from settings race and shared login IP | Medium (CI) | ✅ **2.1.0-beta.29** · `Http/TestCase` + SystemUpdate test order |
| [ISS-135](#iss-135) | Shortcode expand template uses regex denylist instead of full HTML allowlist | Low (defense-in-depth) | ⏳ Deferred · It.67+ hardening slice |
| [ISS-136](#iss-136) | Blog “About author” showed article excerpt/SEO text instead of author bio | Medium (UX / content) | ✅ **2.1.0-beta.38** · settings-based `BlogAuthorSettings` |
| [ISS-137](#iss-137) | Admin user avatar upload failed (`multipart` without boundary) | Medium (admin UX) | ✅ **2.1.0-beta.38** · axios FormData fix |
| [ISS-138](#iss-138) | Blog author had no settings/editor path; fell back to i18n “Redakcia” | Medium (UX) | ✅ **2.1.0-beta.38** · Settings → Content + article author field |
| [ISS-139](#iss-139) | GDPR re-export after anonymize re-aggregated pseudonym-linked comments | Medium (GDPR) | ✅ **2.1.0-beta.38** · skip related rows for anonymized accounts |
| [ISS-140](#iss-140) | Public contact/comments lacked dedicated rate limits; bulk/import/export without caps (API4) | Medium (security) | ✅ **2.1.0-beta.38** · It.80f hardening |
| [ISS-141](#iss-141) | System Update deploy returned 422 after BodyParsingMiddleware (empty JSON body) | High (deploy) | ✅ **2.1.0-beta.40** |

## CI failures (GitHub Actions)

Workflow: [`.github/workflows/ci.yml`](https://github.com/techberode/paginiumcms-architecture/blob/main/.github/workflows/ci.yml)

| CI job | Step | Symptom | Incident |
|---|---|---|---|
| `backend` | PHPUnit | PHPUnit crashed on duplicate HelloWidget Hooks class | [ISS-075](#iss-075) |
| `backend` | PHPUnit | passwordConfirm rollout triggered a cascade of 21 PHPUnit failures | [ISS-076](#iss-076) |
| `backend` | PHPUnit | Login tests returned 429 instead of 401 because lockouts persisted | [ISS-073](#iss-073) |
| `backend` | PHPUnit | Security audit endpoint returned 403 for ADMIN | [ISS-072](#iss-072) |
| `backend` | PHPStan level 8 | Access-control and branding changes introduced ten PHPStan errors | [ISS-074](#iss-074) |
| `backend` | PHPStan level 8 | LocaleScaffoldService projectRoot failed PHPStan and PHPUnit | [ISS-045](#iss-045) |
| `backend` | PHP bootstrap | services.php parse error caused all API requests to fail | [ISS-044](#iss-044) |
| `backend` | PHPUnit | CI PHPUnit output exposed TOTP and 2FA secrets in GitHub job logs | [ISS-120](#iss-120) |
| `backend` | PHPUnit | Four HTTP tests failed after beta.27 (OTP verify 403, SystemUpdate 401/403) | [ISS-125](#iss-125) |
| `backend` | PHPUnit | Post-beta.27 storage/settings path and ScheduledJobRunner mock regressions | [ISS-126](#iss-126) |
| `backend` | PHPUnit / bootstrap | It.71 MetricsController DI unresolved — cascade 233 errors, `bin/console` fatal | [ISS-127](#iss-127) |
| `frontend` | `tsc --noEmit` | `analyticsChartData.ts` import path TS2307 after Phase B | [ISS-132](#iss-132) |
| `frontend` | Vitest | `BackToTopButton` / `ResponsiveLayout` tests without `I18nProvider` | [ISS-133](#iss-133) |
| `backend` | PHPUnit | Intermittent 401/403/404 on Backup, TwoFactor, SystemUpdate HTTP tests (full suite) | [ISS-134](#iss-134) |
| `backend` | PHPUnit | PHPUnit suffered 429, 503, and OTP persistence failures | [ISS-015](#iss-015), [ISS-023](#iss-023) |
| `backend` | PHPUnit | Content index tag, author, and date filters failed PHPUnit | [ISS-038](#iss-038) |
| `backend` | PHPUnit | LogWriter tests failed on virtual files and corrupt JSON | [ISS-039](#iss-039) |
| `backend` | PHPUnit (prod) | Corrupt access log caused JsonException and global API 500 | [ISS-040](#iss-040) |
| `frontend` | `npm run type-check` | Unused React import failed frontend type-checking | [ISS-037](#iss-037) |
| `frontend` | `npm run type-check` | Unused refetch variable failed PagesManager type-checking | [ISS-041](#iss-041) |
| `frontend` | `npm test` | editorToolbar test used global screen against multiple renders | [ISS-043](#iss-043) |
| `frontend` | `npm test` (CI) | Vitest used useI18n without I18nProvider | [ISS-059](#iss-059) |
| `frontend` | `npm run type-check` | Strict TypeScript type-check failures | [ISS-019](#iss-019), [ISS-036](#iss-036) |
| `frontend` | `npm run lint` | ESLint exceeded the 65-warning CI limit | [ISS-020](#iss-020) |
| `frontend` | `npm test` | Vitest worker crashed or hung | [ISS-005](#iss-005), [ISS-010](#iss-010), [ISS-022](#iss-022) |
| `frontend` | `npm audit --audit-level=moderate` | react-router-dom advisories appeared after beta.2 | [ISS-078](#iss-078) |
| `frontend` | `npm ci` / Vitest | Partial @tiptap dependency update caused peer conflict and CI failure | [ISS-081](#iss-081) |
| `backend` | PHPStan level 8 | ContentMetaController called a missing getGroup method | [ISS-080](#iss-080) |
| `backend` | PHPStan (historicky) | PHPStan reported 15 errors | [ISS-006](#iss-006) |

Each canonical record contains an English operational synopsis and links to the full Slovak source, related incidents, commits, CI runs, release evidence, and changelog where those references are available.


---

<a id="iss-001"></a>

## ISS-001 – Debug client-event endpoint returned 404

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-001)

**Severity:** Low (console noise)  
**Status:** ✅ Fixed

### Operational synopsis

The frontend emitted debug events while the backend route was conditionally absent. The route is now stable and becomes a safe no-op when debugging is disabled.

### Evidence and traceability

- **Key technical identifiers:** `XHR POST …/api/debug/client-event [404]`, `APP_DEBUG=true`, `backend/app/Http/Routes/debug.php`, `DebugController`, `frontend/src/utils/debugLog.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-002"></a>

## ISS-002 – GET /api/pages returned 500 on the dashboard

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-002)

**Severity:** High  
**Status:** ✅ Intermittent — diagnose + hardening

### Operational synopsis

A damaged index, malformed content file, cache state, or filesystem permission could break the page list. Diagnostic and tolerant-read hardening were added.

### Evidence and traceability

- **Recorded versions:** `2.0.26`
- **Key technical identifiers:** `/api/pages`, `/api/articles`, `data/index/content.json`, `content/pages/*.md`, `backend/storage/app/content/`, `ContentRepository::findByPath()`, `ContentController::serializeContentList()`, `FileReader::listFiles()`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
curl -s http://192.168.10.26:8081/api/pages | jq '.success, (.data|length)'
# → true, 43

curl -s "http://192.168.10.26:8081/api/pages?page=1&per_page=20" | jq '.success, .meta.total'
# → true, <total>
```
```bash
# Docker log
docker compose logs php | tail -100

# CLI diagnose (ISS-002 tooling)
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --json

# Oprava cache + index
php backend/bin/console content:cache-purge --reindex
# alias:
php backend/bin/rebuild-content-index.php

# Priamy test API (s cookies po login)
curl -s -b cookies.txt http://192.168.10.26:8081/api/pages | jq .
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-003"></a>

## ISS-003 – Phantom or duplicate users appeared repeatedly

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-003)

**Severity:** Medium  
**Status:** ✅ Backend hardening

### Operational synopsis

Backup files, invalid user JSON, and open registration could look like newly generated users. Repository filtering, validation, deduplication, and backup retention were added.

### Evidence and traceability

- **Key technical identifiers:** `/users`, `data/users/`, `*.json.backup.Ymd_His`, `/api/auth/register`, `UserRepository::getAllUserFiles()`, `UserRepository::findAll()`, `FileWriter::pruneBackups()`, `user_xxx.json.backup.20260718_120000`, `user_xxx.json`, `ls …/data/users/*.json`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
# Náhľad backup súborov medzi používateľmi (NIE samotné účty!)
ls -la backend/storage/app/content/data/users/*.backup.* 2>/dev/null

# Odstránenie starých backupov (po backup celého priečinka!)
find backend/storage/app/content/data/users -name '*.backup.*' -delete
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-004"></a>

## ISS-004 – navigation.json.backup.* files accumulated

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-004)

**Severity:** Low  
**Status:** ✅ Backup retention

### Operational synopsis

Timestamped write backups accumulated without bounded retention. FileWriter now keeps only the configured recent backup set.

### Evidence and traceability

- **Key technical identifiers:** `data/`, `navigation.json.backup.20260718_104530`, `FileWriter::write(..., backup=true)`, `FileWriter::pruneBackups()`, `navigation.json.backup.*`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-005"></a>

## ISS-005 – Vitest worker crashed or hung

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-005)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed

### Operational synopsis

An unstable dependency in useBulkSelection caused the Vitest worker to loop or exit. Stable derived dependencies removed the crash.

### Evidence and traceability

- **Key technical identifiers:** `npm test`, `frontend/src/hooks/useBulkSelection.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-006"></a>

## ISS-006 – PHPStan reported 15 errors

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-006)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed

### Operational synopsis

Level-8 static analysis found fifteen type defects. The affected backend classes and tests were corrected until the gate was clean.

### Evidence and traceability

- **Key technical identifiers:** `MediaRepository`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-007"></a>

## ISS-007 – Dashboard displayed the wrong user count

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-007)

**Severity:** Low  
**Status:** ✅ Fixed

### Operational synopsis

The dashboard read the wrong API response shape. It now counts the users array inside the response payload.

### Evidence and traceability

- **Key technical identifiers:** `DashboardView.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-008"></a>

## ISS-008 – Password fields were served over HTTP

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-008)

**Severity:** Info  
**Status:** ⏳ HTTPS in production

### Operational synopsis

The browser warning was caused by password inputs on plain HTTP, not by form validation. Production deployment must terminate TLS before nginx/PHP.

### Evidence and traceability

- **Key technical identifiers:** `/login`, `/users`, `/settings`, `http://192.168.10.26:8081`, `docs/deploy/nginx-paginium-test.conf`, `https://192.168.10.26:8443/settings`, `docs/deploy/NGINX_API.md`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
# Self-signed cert pre LAN (192.168.10.26)
sudo mkdir -p /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/paginium-test.key \
  -out /etc/nginx/ssl/paginium-test.crt \
  -subj "/CN=192.168.10.26"
```
```nginx
listen 8443 ssl;
listen [::]:8443 ssl;
ssl_certificate     /etc/nginx/ssl/paginium-test.crt;
ssl_certificate_key /etc/nginx/ssl/paginium-test.key;
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-009"></a>

## ISS-009 – Settings crashed with n.max is not a function

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-009)

**Severity:** High  
**Status:** ✅ Fixed

### Operational synopsis

Validation rules applied min/max after wrapping the schema as optional. Constraints are now applied to the inner type before optional wrapping.

### Evidence and traceability

- **Key technical identifiers:** `/settings`, `zodFromRules.ts`, `npm test -- src/validation/zodFromRules.test.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-010"></a>

## ISS-010 – Vitest stderr contained act() and router future-flag warnings

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-010)

**Severity:** Low (CI noise)  
**Status:** ✅ Fixed (2.0.24)

### Operational synopsis

Tests used asynchronous interactions without proper waiting and omitted router future flags. Shared wrappers and user-event based tests removed the noise.

### Evidence and traceability

- **Recorded versions:** `2.0.24`
- **Commit:** [`b9a740f`](https://github.com/techberode/paginiumcms-architecture/commit/b9a740f)
- **Key technical identifiers:** `npm test`, `DeveloperUnlockGate.test.tsx`, `MediaManager.test.tsx`, `frontend/src/test/renderWithRouter.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-011"></a>

## ISS-011 – ESLint warning baseline and technical debt

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-011)

**Severity:** Low (technical debt)  
**Status:** ⏳ 57/65 baseline, gradual cleanup

### Operational synopsis

Warnings remain controlled technical debt under a CI baseline. The policy is to reduce the count without silently increasing the allowed maximum.

### Evidence and traceability

- **Recorded versions:** `2.0.26`
- **Related incidents:** [ISS-020](#iss-020)
- **Key technical identifiers:** `npm run lint`, `@typescript-eslint/no-explicit-any`, `client.ts`, `useApi.ts`, `react-refresh/only-export-components`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-012"></a>

## ISS-012 – CSRF middleware was not wired into mutating routes

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-012)

**Severity:** Medium  
**Status:** ✅ Fixed — `CsrfMiddleware` (synchronizer-token)

### Operational synopsis

A CSRF manager existed but mutating routes were not globally protected. A SPA-compatible synchronizer-token middleware and refresh flow were introduced.

### Evidence and traceability

- **Key technical identifiers:** `CsrfProtectionManager`, `CsrfMiddleware`, `backend/app/Http/Middleware/CsrfMiddleware.php`, `POST/PUT/PATCH/DELETE`, `/api/auth/login`, `/api/auth/register`, `/api/auth/reset-password`, `/api/auth/verify-reset-token`, `/api/auth/csrf-token`, `/api/auth/sso`, `/api/contact`, `/api/comments`, `/api/debug/client-event`, `APP_ENV=testing`, `bootstrap/app.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="feature-login-background"></a>

## Login background — media-library and local upload support (2.0.46)

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#feature-login-background)

The login and registration settings originally accepted only a background-image URL. Version 2.0.46 added selection from the media library, local upload, preview, removal, correct `/storage/` path handling, and paired SK/EN interface text.

**Verification:** select or upload an image in the login settings, save it, and verify the result on `/login`.


---

<a id="iss-013"></a>

## ISS-013 – ntfy private topics failed without authentication

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-013)

**Severity:** Medium  
**Status:** ✅ It.47 (Bearer/Basic + test-connector)

### Operational synopsis

The ntfy adapter sent unauthenticated requests. Bearer and Basic modes plus a connector verification endpoint were added.

### Evidence and traceability

- **Key technical identifiers:** `NtfyAdapter::buildAuthHeaders()`, `POST /api/admin/notifications/test-connector`, `/notifications`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-014"></a>

## ISS-014 – Development CORS wildcards could remain active with an incorrect APP_ENV

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-014)

**Severity:** Low  
**Status:** ⏳ Verify deployment

### Operational synopsis

Non-production CORS patterns are intentionally broad, so a wrongly deployed APP_ENV could expose credentialed origins. Deployment must verify production mode.

### Evidence and traceability

- **Key technical identifiers:** `APP_ENV`, `APP_ENV=production`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-015"></a>

## ISS-015 – PHPUnit suffered 429, 503, and OTP persistence failures

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-015)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.25)

### Operational synopsis

Shared test state leaked rate limits, maintenance mode, OTP files, and caches between tests. The testing bootstrap now resets and isolates these stores.

### Evidence and traceability

- **Commit:** [`f54361d`](https://github.com/techberode/paginiumcms-architecture/commit/f54361d)
- **Key technical identifiers:** `settings.json`, `otp-challenges.json`, `APP_ENV=testing`, `TestCase::setUp()`, `SettingsRepository::setGroup()`, `RateLimitMiddleware`, `SettingsRepository`, `./vendor/bin/phpunit`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-016"></a>

## ISS-016 – PHPStan phpVersion did not match composer.json

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-016)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.25)

### Operational synopsis

Static analysis targeted PHP 8.5 while Composer declared an 8.4 floor. PHPStan now analyses against the minimum supported runtime.

### Evidence and traceability

- **Commit:** [`d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- **Key technical identifiers:** `composer.json`, `./vendor/bin/phpstan analyse backend --level=8`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-017"></a>

## ISS-017 – PHPStan reported match.alwaysTrue in bulk controllers

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-017)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.25)

### Operational synopsis

Guard clauses narrowed the action before a match expression, making branches statically impossible. Explicit conditional branches replaced the match.

### Evidence and traceability

- **Commit:** [`d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- **Key technical identifiers:** `MessageController::bulkAction()`, `CommentsController::bulkAction()`, `if / elseif`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-018"></a>

## ISS-018 – TrashController passed a possible false fopen result as a resource

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-018)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.25)

### Operational synopsis

fopen may return false, but the result was passed as a stream resource. The controller now checks failure and returns a controlled error.

### Evidence and traceability

- **Commit:** [`d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- **Key technical identifiers:** `TrashControllerTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```php
$handle = fopen($path, 'rb');
if ($handle === false) {
    return $this->json->error($response, 'Nepodarilo sa otvoriť zálohu', 500);
}
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-019"></a>

## ISS-019 – Strict TypeScript type-check failures

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-019)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.25)

### Operational synopsis

Several frontend DTO casts, optional values, and unused variables violated strict TypeScript rules. Typed intermediate objects and runtime guards fixed the gate.

### Evidence and traceability

- **Recorded versions:** `2.0.25`
- **Commit:** [`5398b48`](https://github.com/techberode/paginiumcms-architecture/commit/5398b48)
- **Key technical identifiers:** `npm run type-check`, `api/comments.ts`, `api/workflows.ts`, `/workflows/otp/verify`, `MarkdownEditor.tsx`, `BackupManager.tsx`, `Navbar.tsx`, `comments.ts`, `workflows.ts`, `cd frontend && npm run type-check`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-020"></a>

## ISS-020 – ESLint exceeded the 65-warning CI limit

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-020)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.26)

### Operational synopsis

Hook dependency warnings pushed ESLint beyond the accepted baseline. Dependencies and callbacks were stabilized instead of relaxing the limit.

### Evidence and traceability

- **Recorded versions:** `2.0.26`
- **Commit:** [`d24f0e0`](https://github.com/techberode/paginiumcms-architecture/commit/d24f0e0)
- **Key technical identifiers:** `npm run lint`, `package.json`, `react-hooks/exhaustive-deps`, `useToast.ts`, `MediaManager`, `cd frontend && npm run lint`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-021"></a>

## ISS-021 – Redundant is_array checks failed PHPStan

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-021)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.26)

### Operational synopsis

PHPStan correctly identified is_array calls on values already guaranteed to be arrays. Redundant guards were removed while mixed-item validation remained.

### Evidence and traceability

- **Commit:** [`d24f0e0`](https://github.com/techberode/paginiumcms-architecture/commit/d24f0e0)
- **Key technical identifiers:** `ApplicationLogReader.php`, `JsonHelper::decode()`, `./vendor/bin/phpstan analyse backend --level=8`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-022"></a>

## ISS-022 – MediaManager Vitest assertions were brittle

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-022)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.26)

### Operational synopsis

Text queries matched duplicated or unstable UI strings and a toast mock changed identity on every render. Role-based queries and stable mocks fixed the tests.

### Evidence and traceability

- **Related incidents:** [ISS-020](#iss-020)
- **Key technical identifiers:** `npm test`, `MediaManager`, `MediaManager.test.tsx`, `findByRole('button', { name: /Preview hero\.png/i })`, `findByRole('checkbox', { name: /Select hero\.png/i })`, `cd frontend && npm test -- src/components/backend/MediaManager.test.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-023"></a>

## ISS-023 – Admin draft search test was flaky

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-023)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.29)

### Operational synopsis

The test searched using a non-deterministic slug and accidentally overwrote the slug in front matter. A deterministic token and explicit slug fixed the test.

### Evidence and traceability

- **Commit:** [`3fd8323`](https://github.com/techberode/paginiumcms-architecture/commit/3fd8323)
- **Key technical identifiers:** `SearchControllerTest::testAdminSearchIncludesDraftPages`, `TrashControllerTest`, `./vendor/bin/phpunit backend/tests/Http/Controllers/Content/SearchControllerTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
Failed asserting that an array contains 'seo-test-<uniqid>'.
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-024"></a>

## ISS-024 – AuthMiddleware caused HTTP 500 on protected routes

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-024)

**Severity:** Critical  
**Status:** ✅ Fixed (2.0.29)

### Operational synopsis

Dependency injection supplied the wrong constructor shape after AuthMiddleware changed. Session touching moved behind the authentication interface to preserve DI compatibility.

### Evidence and traceability

- **Key technical identifiers:** `POST /api/admin/settings/monitoring`, `POST /api/debug/client-event`, `AuthMiddleware`, `SessionManager`, `bootstrap/app.php`, `AuthenticationManager`, `AuthControllerTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
AuthMiddleware::__construct(): Argument #2 ($session) must be of type SessionManager, AuthenticationInterface given
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-025"></a>

## ISS-025 – Users were logged out while editing or saving

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-025)

**Severity:** High  
**Status:** ✅ Fixed (2.0.29)

### Operational synopsis

Short lifetimes, multiple session objects, proxy IP binding, and eager frontend 401 redirects combined to log out active editors. Session ownership and keepalive were hardened.

### Evidence and traceability

- **Key technical identifiers:** `/login`, `SESSION_LIFETIME=120`, `SessionManager`, `SESSION_STRICT=true`, `SecureSessionManager`, `TRUSTED_PROXIES`, `AuthenticationManager::touchSession()`, `AuthMiddleware`, `bootstrap/session.php`, `session.cookie_path=/`, `client.ts`, `/api/auth/me`, `POST /api/auth/login`, `GET /api/auth/me`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```env
SESSION_LIFETIME=28800
SESSION_STRICT=false
SESSION_USE_STRICT_MODE=true
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-026"></a>

## ISS-026 – SESSION_USE_STRICT_MODE was confused with SESSION_STRICT

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-026)

**Severity:** Medium (ops)  
**Status:** ✅ Documented (2.0.29)

### Operational synopsis

The two environment variables control different security mechanisms. Documentation now separates PHP strict session IDs from Paginium IP/user-agent binding.

### Evidence and traceability

- **Key technical identifiers:** `SESSION_USE_STRICT_MODE=false`, `.env`, `SESSION_USE_STRICT_MODE`, `SESSION_STRICT`, `SecureSessionManager`, `.env.example`, `SESSION_STRICT=false`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-027"></a>

## ISS-027 – PHPUnit produced false login-401 debug records

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-027)

**Severity:** Low (diagnostics)  
**Status:** ✅ Fixed (2.0.29)

### Operational synopsis

HTTP tests wrote expected failed-login activity into development debug logs. DebugEventLogger now stays disabled in the testing environment.

### Evidence and traceability

- **Key technical identifiers:** `storage/logs/debug/*.log`, `APP_ENV=testing`, `DebugEventLogger::isEnabled()`, `./vendor/bin/phpunit`, `APP_DEBUG`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```json
{"event":"http.request","context":{"method":"POST","path":"/api/auth/login"}}
{"event":"http.response","context":{"status":401,...}}
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-028"></a>

## ISS-028 – Production frontend build failed on malformed JSX

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-028)

**Severity:** High (deploy)  
**Status:** ✅ Fixed (2.0.29)

### Operational synopsis

A misplaced closing element broke the SettingsView JSX tree. The 2FA card structure was corrected and the production build gate verified the fix.

### Evidence and traceability

- **Key technical identifiers:** `npm run build:prod`, `CacheManagerPanel`, `</div>`, `cd frontend && npm run build:prod`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
Adjacent JSX elements must be wrapped in an enclosing tag.
SettingsView.tsx:162
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-029"></a>

## ISS-029 – Login briefly succeeded and then returned to /login

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-029)

**Severity:** High  
**Status:** ✅ 2.0.29 session; **2.0.30** 2FA loop

### Operational synopsis

Session loss or an eager 401 redirect produced a login loop. Backend session checks and frontend session probing now distinguish expiry, 2FA, and network errors.

### Evidence and traceability

- **Recorded versions:** `2.0.29`, `2.0.30`
- **Related incidents:** [ISS-025](#iss-025), [ISS-030](#iss-030), [ISS-034](#iss-034)
- **Key technical identifiers:** `/login`, `/api/auth/me`, `AuthController`, `POST /api/auth/login`, `GET /api/auth/me`, `php backend/bin/console security:clear-lockouts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-030"></a>

## ISS-030 – 2FA setup QR disappeared and redirected to TOTP login

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-030)

**Severity:** High  
**Status:** ✅ Fixed (2.0.30)

### Operational synopsis

The setup flow reused the same state as post-login TOTP verification. A setup_pending state now keeps the QR flow inside account security until verification.

### Evidence and traceability

- **Key technical identifiers:** `/account/security`, `/login`, `/api/auth/2fa/status`, `TwoFactorMiddleware`, `AuthController::login`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-031"></a>

## ISS-031 – New staff users had twoFactorEnabled without a secret

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-031)

**Severity:** Critical  
**Status:** ✅ Fixed (2.0.30)

### Operational synopsis

Policy code enabled 2FA before a secret existed. Staff accounts now enter 2FA only through the provisioning endpoint and may reprovision until first verification.

### Evidence and traceability

- **Key technical identifiers:** `POST /api/auth/2fa/enable`, `UserController`, `TwoFactorController::enable()`, `backend/storage/app/users/{id}.json`, `/account/security`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```json
"twoFactorEnabled": false,
"twoFactorSecret": null,
"twoFactorVerifiedAt": null
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-032"></a>

## ISS-032 – twoFactorVerifiedAt was not persisted to user JSON

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-032)

**Severity:** High  
**Status:** ✅ Fixed (2.0.30)

### Operational synopsis

User serialization omitted the verification timestamp. The field is now included in flat-file hydration and extraction.

### Evidence and traceability

- **Key technical identifiers:** `UserRepository::extract()`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-033"></a>

## ISS-033 – Frontend 401 interceptor caused a full-page double login

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-033)

**Severity:** High  
**Status:** ✅ Fixed (2.0.30)

### Operational synopsis

The Axios interceptor performed a hard browser redirect on every 401. Auth events and React Router now preserve state and distinguish TOTP from expiry.

### Evidence and traceability

- **Key technical identifiers:** `window.location.href = '/login'`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-034"></a>

## ISS-034 – Development environment lacked a TOTP policy switch

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-034)

**Severity:** Medium (DX)  
**Status:** ✅ Fixed (2.0.30)

### Operational synopsis

Development needed a safe way to relax mandatory TOTP. TwoFactorPolicy now permits an environment switch only outside production.

### Evidence and traceability

- **Key technical identifiers:** `.env`, `APP_ENV=production`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```env
APP_ENV=development
TWO_FACTOR_REQUIRED=false
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-035"></a>

## ISS-035 – ClientIpResolver contained a dead null-coalescing fallback

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-035)

**Severity:** Low (CI)  
**Status:** ✅ Fixed (2.0.29 hotfix)

### Operational synopsis

explode always returns a non-empty list, so the null-coalescing fallback was unreachable. The dead expression was removed.

### Evidence and traceability

- **Recorded versions:** `2.0.29`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
Offset 0 on non-empty-list<string> on left side of ?? always exists
ClientIpResolver.php — $parts[0] ?? $remoteAddr
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-036"></a>

## ISS-036 – 2FA setup_pending and setUser broke frontend type-checking

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-036)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (2.0.30 hotfix `3fbc595`)

### Operational synopsis

The backend snake_case field and frontend camelCase model diverged, and the component called an unavailable setter. Mapping and context APIs were corrected.

### Evidence and traceability

- **Recorded versions:** `2.0.30`
- **Commit:** [`f5061e6`](https://github.com/techberode/paginiumcms-architecture/commit/f5061e6)
- **Commit:** [`3fbc595`](https://github.com/techberode/paginiumcms-architecture/commit/3fbc595)
- **Key technical identifiers:** `frontend/src/api/auth.ts`, `frontend/src/api/client.ts`, `frontend/src/components/auth/TwoFactorSettings.tsx`, `frontend/src/components/auth/TwoFactorSettings.test.tsx`, `auth.ts`, `cd frontend && npm run type-check && npm test -- --run src/components/auth/TwoFactorSettings.test.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
src/api/auth.ts(182,35) — setup_pending does not exist on ApiResponse<…>
src/components/auth/TwoFactorSettings.tsx(65,9) — Cannot find name 'setUser'
src/components/auth/TwoFactorSettings.test.tsx — mock missing setupPending
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-037"></a>

## ISS-037 – Unused React import failed frontend type-checking

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-037)

**Severity:** Low (CI)  
**Status:** ✅ Fixed (hotfix `64cc894`)

### Operational synopsis

The automatic JSX runtime made an explicit React import unused. Removing the import restored the strict type-check gate.

### Evidence and traceability

- **Commit:** [`fbb574b`](https://github.com/techberode/paginiumcms-architecture/commit/fbb574b)
- **Commit:** [`64cc894`](https://github.com/techberode/paginiumcms-architecture/commit/64cc894)
- **Key technical identifiers:** `SettingsView.test.tsx`, `cd frontend && npm run type-check`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
src/components/backend/SettingsView.test.tsx(4,1): error TS6133: 'React' is declared but its value is never read.
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-038"></a>

## ISS-038 – Content index tag, author, and date filters failed PHPUnit

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-038)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (`54b013c`)

### Operational synopsis

Content index tests exposed missing or inconsistent tag, author, date, and distinct-tag behavior. Index serialization and filtering were corrected.

### Evidence and traceability

- **Commit:** [`743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- **Commit:** [`05a4800`](https://github.com/techberode/paginiumcms-architecture/commit/05a4800)
- **Commit:** [`54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- **Key technical identifiers:** `ContentRepositoryTest.php`, `ContentIndexEntry::normalizeIndexedDate()`, `ContentIndexEntry::normalizeTags()`, `ContentIndexService::applyIndexFilters()`, `ContentIndexEntry.php`, `ContentIndexService.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
php vendor/bin/phpunit --filter ContentRepositoryTest::testFindArticlesPaginatedFiltersByTagAuthorAndDate
php vendor/bin/phpunit --filter ContentRepositoryTest::testListDistinctTagsAndCountIndexed
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-039"></a>

## ISS-039 – LogWriter tests failed on virtual files and corrupt JSON

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-039)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (`54b013c`)

### Operational synopsis

LogWriter tests assumed files existed and did not model damaged JSON safely. Virtual filesystem setup and corrupt-log recovery were hardened.

### Evidence and traceability

- **Commit:** [`743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- **Commit:** [`54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- **Key technical identifiers:** `LogWriterTest.php`, `Failed asserting that file "vfs://storage/logs/app/YYYY-MM-DD.json" exists`, `FileHelper.php:36`, `vfs://`, `FileHelper::read()`, `LogWriter::salvageCorruptLogPayload()`, `LogWriter::ensureStorageDirectory()`, `LogWriter::readLogFile()`, `LogWriter.php`, `php vendor/bin/phpunit --filter LogWriterTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-040"></a>

## ISS-040 – Corrupt access log caused JsonException and global API 500

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-040)

**Severity:** Critical (prod)  
**Status:** ✅ Fixed (`743e922`)

### Operational synopsis

One malformed access-log file threw JsonException during global request processing. Readers now isolate or recover corrupt records instead of taking down the API.

### Evidence and traceability

- **Commit:** [`743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- **Commit:** [`54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- **Related incidents:** [ISS-039](#iss-039)
- **Key technical identifiers:** `backend/app/storage/logs/app/YYYY-MM-DD.json`, `RequestLoggingMiddleware`, `LogWriter::decodeLogPayload()`, `RequestLoggingMiddleware::safeLogRequest()`, `LogWriter.php`, `RequestLoggingMiddleware.php`, `./scripts/iteration-gate.sh`, `/api/settings/public`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
GET /api/seo/page/home 500
GET /api/settings/public 500
GET /api/auth/me 500
POST /api/debug/client-event 500
```
```
Uncaught JsonException: Syntax error
  FileHelper.php:18 (JsonHelper::decode)
  LogWriter.php → AccessLogService → RequestLoggingMiddleware
```
```bash
mv backend/app/storage/logs/app/$(date +%Y-%m-%d).json \
   backend/app/storage/logs/app/$(date +%Y-%m-%d).json.bak
echo '[]' > backend/app/storage/logs/app/$(date +%Y-%m-%d).json
git pull   # 743e922 + 54b013c
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-041"></a>

## ISS-041 – Unused refetch variable failed PagesManager type-checking

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-041)

**Severity:** Low (CI)  
**Status:** ✅ Fixed (hotfix 2.0.40)

### Operational synopsis

PagesManager retained an unused refetch binding after a refactor. Removing it restored the no-unused-locals check.

### Evidence and traceability

- **Recorded versions:** `2.0.40`
- **Key technical identifiers:** `PagesManager.tsx`, `cd frontend && npm run type-check`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
src/components/backend/PagesManager.tsx(161,38): error TS6133: 'refetch' is declared but its value is never read.
```
```ts
// pred
const { data: listData, isLoading, refetch } = useAdminListQuery({ ... })

// po
const { data: listData, isLoading } = useAdminListQuery({ ... })
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-042"></a>

## ISS-042 – First login attempt failed while the second succeeded

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-042)

**Severity:** High (auth UX)  
**Status:** ✅ Fixed (**2.0.43**)

### Operational synopsis

The first credentials request succeeded but the immediate session probe failed, forcing a second login. Cookie, proxy, and probe sequencing were corrected.

### Evidence and traceability

- **Recorded versions:** `2.0.42`
- **Related incidents:** [ISS-029](#iss-029), [ISS-033](#iss-033)
- **Key technical identifiers:** `GET /api/auth/me`, `POST /api/auth/login`, `/api/auth/me`, `http://localhost:3025`, `/api`, `/me`, `POST /login`, `npm run dev`, `/api/`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-043"></a>

## ISS-043 – editorToolbar test used global screen against multiple renders

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-043)

**Severity:** Low (CI)  
**Status:** ✅ Fixed (2.0.42 It.54)

### Operational synopsis

A test queried the global document after rendering multiple toolbar profiles. Queries are now scoped to the intended render container.

### Evidence and traceability

- **Recorded versions:** `2.0.41`
- **Commit:** [`8526c19`](https://github.com/techberode/paginiumcms-architecture/commit/8526c19)
- **Key technical identifiers:** `editorToolbar.test.tsx`, `npm test -- src/components/backend/editorToolbar.test.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-044"></a>

## ISS-044 – services.php parse error caused all API requests to fail

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-044)

**Severity:** Critical  
**Status:** ✅ Fixed (**2.0.45**)

### Operational synopsis

A syntax error in the services container prevented application bootstrap. Correcting the service definition restored all API routes.

### Evidence and traceability

- **Recorded versions:** `2.0.45`
- **Key technical identifiers:** `ValidationController`, `backend/app/Http/Config/services.php`, `POST /api/debug/client-event`, `php -l backend/app/Http/Config/services.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```php
    },
        ->constructor(get(JsonResponder::class)),
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-045"></a>

## ISS-045 – LocaleScaffoldService projectRoot failed PHPStan and PHPUnit

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-045)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed (**2.0.45**)

### Operational synopsis

LocaleScaffoldService referenced a projectRoot property that was not defined consistently. Constructor state and tests were aligned.

### Evidence and traceability

- **Recorded versions:** `2.0.45`
- **Key technical identifiers:** `LocaleScaffoldService.php`, `./scripts/iteration-gate.sh`, `LocaleScaffoldService`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```php
private string $projectRoot;

public function __construct(
    private SupportedLocalesRegistry $locales,
    ?string $projectRoot = null,
) {
    $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
}
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-046"></a>

## ISS-046 – Audit events were stored under the app category

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-046)

**Severity:** High (audit)  
**Status:** ✅ Fixed (**2.0.46**)

### Operational synopsis

Audit records used the generic app category and disappeared from audit views. AuditLogger and readers now preserve the audit source/category.

### Evidence and traceability

- **Recorded versions:** `2.0.46`
- **Related incidents:** [ISS-047](#iss-047)
- **Key technical identifiers:** `storage/logs/app/*.json`, `AuditTrailService::logAuditEvent()`, `LoggerInterface::writeEntry(LogEntry)`, `AuditTrailService`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-047"></a>

## ISS-047 – Dashboard activity overview was empty

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-047)

**Severity:** High (admin UX)  
**Status:** ✅ Fixed (**2.0.46**)

### Operational synopsis

The dashboard queried or interpreted activity data incorrectly. The overview now consumes the repaired audit feed.

### Evidence and traceability

- **Recorded versions:** `2.0.46`
- **Related incidents:** [ISS-046](#iss-046), [ISS-048](#iss-048)
- **Key technical identifiers:** `/dashboard`, `GET /api/admin/audit/stats`, `AuditTrailService::isAuditEntry()`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-048"></a>

## ISS-048 – Audit messages were unreadable or used the wrong locale

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-048)

**Severity:** Medium (audit UX)  
**Status:** ✅ Fixed (**2.0.46**)

### Operational synopsis

Stored audit summaries were inconsistent and sometimes used the wrong language. Structured context is now reformatted for the active locale.

### Evidence and traceability

- **Recorded versions:** `2.0.46`
- **Key technical identifiers:** `/audit`, `formatAuditEvent.ts`, `formatAuditEvent.test.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-049"></a>

## ISS-049 – Daily log file 2026-07-21.json was corrupt

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-049)

**Severity:** Medium (ops)  
**Status:** ✅ Fixed (**2.0.46**)

### Operational synopsis

A damaged daily JSON log blocked normal reading. Corruption handling and log repair prevented a single file from disabling the section.

### Evidence and traceability

- **Recorded versions:** `2.0.46`
- **Related incidents:** [ISS-046](#iss-046), [ISS-047](#iss-047)
- **Key technical identifiers:** `backend/app/storage/logs/app/`, `LogWriter::decodeLogPayload()`, `JsonHelper::decode('')`, `2026-07-21.json`, `rm backend/app/storage/logs/app/2026-07-21.json.corrupt-*`, `./vendor/bin/phpunit backend/tests/Core/Logging/Services/LogWriterTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-050"></a>

## ISS-050 – Logs section used the wrong reader path and appeared empty

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-050)

**Severity:** High (admin UX)  
**Status:** ✅ Fixed (**2.0.46**)

### Operational synopsis

ApplicationLogReader pointed at a path different from the active log writer. Storage path resolution was unified.

### Evidence and traceability

- **Recorded versions:** `2.0.46`
- **Related incidents:** [ISS-049](#iss-049)
- **Key technical identifiers:** `/logs`, `backend/app/storage/logs/app/`, `Http/Config/services.php`, `backend/storage/logs/`, `app/`, `/logs?severity=info`, `backend/app/storage/logs/*`, `ApplicationLogReader::severityStats()`, `LogController`, `./vendor/bin/phpunit backend/tests/Core/Logging/ApplicationLogReaderTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-051"></a>

## ISS-051 – DevTokenGenerator exception crashed production boot and CLI

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-051)

**Severity:** Critical (boot/CLI)  
**Status:** ✅ Fixed (security_fix hotfix)

### Operational synopsis

A development token generator threw during production container construction. Production now fails safely without instantiating development-only token behavior.

### Evidence and traceability

- **Key technical identifiers:** `APP_ENV=production`, `scripts/run-all-tests.zsh`, `DevTokenGenerator::class`, `APP_ENV`, `backend/.env`, `bootstrap/app.php`, `php backend/bin/console content:diagnose`, `APP_DEBUG=true`, `APP_ENV=development`, `backend/app/Http/Config/services.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
Uncaught RuntimeException: DEV_UNLOCK_SECRET must be configured outside local development.
  in backend/app/Http/Config/services.php:482
```
```
[ Content diagnose (backend/bin/console) ]
Stats: Failed | issues: 0
ISSUE — thrown in .../backend/app/Http/Config/services.php
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-052"></a>

## ISS-052 – TOTP, SMTP, SSO, and ntfy secrets were stored in plaintext

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-052)

**Severity:** Medium (security)  
**Status:** ✅ Fixed — `EncryptionService` + `APP_KEY`

### Operational synopsis

Sensitive settings and TOTP seeds were persisted in clear text. EncryptionService and APP_KEY-backed at-rest encryption protect supported fields.

### Evidence and traceability

- **Key technical identifiers:** `data/users/*.json`, `data/settings.json`, `EncryptionService`, `backend/app/Core/Security/Services/EncryptionService.php`, `APP_KEY`, `UserRepository`, `SettingsRepository`, `SettingsSchema::secretKeys()`, `EncryptionServiceTest`, `UserRepositoryTest`, `SettingsRepositoryTest`, `EncryptionService.php`, `UserRepository.php`, `SettingsRepository.php`, `SettingsSchema.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-053"></a>

## ISS-053 – Control characters enabled log and CSV injection

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-053)

**Severity:** Low–Medium (security)  
**Status:** ✅ Fixed — `LogSanitizer`

### Operational synopsis

CR/LF and control characters could forge log lines or spreadsheet cells. LogSanitizer now normalizes log and CSV exports.

### Evidence and traceability

- **Key technical identifiers:** `backend/app/Support/LogSanitizer.php`, `AccessLogService`, `SecurityAuditStore::exportCsv()`, `LogSanitizer.php`, `AccessLogService.php`, `FirewallIncidentLogger.php`, `SecurityAuditStore.php`, `SecurityLogger.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-054"></a>

## ISS-054 – Admin-configurable outbound URLs enabled SSRF paths

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-054)

**Severity:** Low–Medium (security)  
**Status:** ✅ Fixed — `OutboundUrlGuard`

### Operational synopsis

OAuth, ntfy, webhook, and related URLs could reach unsafe destinations. OutboundUrlGuard enforces scheme, DNS, redirect, and network-range policy.

### Evidence and traceability

- **Key technical identifiers:** `OutboundUrlGuard`, `backend/app/Core/Security/Services/OutboundUrlGuard.php`, `https://`, `10/8`, `172.16/12`, `192.168/16`, `::1`, `OAuthSsoService`, `OutboundUrlGuardTest`, `OutboundUrlGuard.php`, `OAuthSsoService.php`, `NtfyAdapter.php`, `WebhookAdapter.php`, `DiscordAdapter.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-055"></a>

## ISS-055 – Path ACL existed but was not enforced for content and media

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-055)

**Severity:** Medium (security)  
**Status:** ✅ Fixed — `ContentPathAclGuard`

### Operational synopsis

Path ACL rules existed but content and media operations bypassed them. ContentPathAclGuard is now integrated into protected operations.

### Evidence and traceability

- **Recorded versions:** `2.0.51`
- **Key technical identifiers:** `PathAclService`, `/security/acl`, `data/security/acl.json`, `ContentPathAclGuard`, `PathAclService::normalizeStoragePath()`, `pages/foo.md`, `content/pages/foo`, `ContentController`, `DraftController`, `MediaController`, `PathAclServiceTest`, `ContentPathAclGuardTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-056"></a>

## ISS-056 – WAF scanned URI and headers but not POST or JSON bodies

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-056)

**Severity:** Medium (security)  
**Status:** ✅ Fixed — body scan + editor exempt

### Operational synopsis

The application firewall ignored request bodies. Bounded POST/JSON scanning was added with explicit editor-safe exemptions.

### Evidence and traceability

- **Key technical identifiers:** `FirewallMiddleware`, `multipart/form-data`, `/api/pages`, `/api/articles`, `/api/drafts`, `/api/admin/code-editor`, `APP_ENV=testing`, `FirewallMiddlewareTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-057"></a>

## ISS-057 – UserRepository lookups scanned every JSON file in O(n)

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-057)

**Severity:** Low (performance)  
**Status:** ✅ Fixed — `UserIndexService` + `data/index/users.json`

### Operational synopsis

Every user lookup scanned all JSON files. UserIndexService now maintains an indexed lookup while the files remain the source of truth.

### Evidence and traceability

- **Key technical identifiers:** `UserRepository::findByEmail()`, `data/users/*.json`, `UserIndexService`, `data/index/users.json`, `ContentIndexService`, `UserRepository`, `bootstrap/app.php`, `Modules/Security/Config/services.php`, `UserIndexServiceTest`, `UserRepositoryTest`, `UserIndexService.php`, `UserRepository.php`, `scripts/run-all-tests.zsh`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-058"></a>

## ISS-058 – OTP lacked a dedicated limiter and resend reset attempts

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-058)

**Severity:** Medium (security)  
**Status:** ✅ Fixed — `Otp*RateLimitMiddleware` + `resend_count`

### Operational synopsis

OTP endpoints lacked their own limits and resend could reset attempts. Dedicated middleware and resend counters now enforce the workflow.

### Evidence and traceability

- **Key technical identifiers:** `/api/auth/register*`, `/api/admin/workflows/otp/*`, `OtpStartRateLimitMiddleware`, `OtpVerifyRateLimitMiddleware`, `OtpResendRateLimitMiddleware`, `bootstrap/app.php`, `workflows.php`, `OtpRateLimitMiddlewareTest`, `OtpWorkflowServiceTest`, `OtpRateLimitMiddleware.php`, `OtpVerifyRateLimitMiddleware.php`, `OtpResendRateLimitMiddleware.php`, `OtpStartRateLimitMiddleware.php`, `OtpWorkflowService.php`, `OtpChallengeStore.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-059"></a>

## ISS-059 – Vitest used useI18n without I18nProvider

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-059)

**Severity:** Low (CI)  
**Status:** ✅ Fixed — `renderWithProviders` (**2.0.47**)

### Operational synopsis

Unit tests rendered components outside the i18n provider. A shared renderWithProviders helper supplies the required context.

### Evidence and traceability

- **Recorded versions:** `2.0.47`
- **Commit:** [`f0a885c787e2234f8c117921e75e42b555bfe5a5`](https://github.com/techberode/paginiumcms-architecture/commit/f0a885c787e2234f8c117921e75e42b555bfe5a5)
- **Related incidents:** [ISS-022](#iss-022)
- **Key technical identifiers:** `@testing-library/react`, `MediaPreviewLightbox.test.tsx`, `MediaPreviewLightbox.tsx:35`, `SitePreviewModal.test.tsx`, `SitePreviewModal.tsx:137`, `editorToolbar.test.tsx`, `MarkdownContentEditor.tsx:44`, `HealthPanel.test.tsx`, `HealthPanel.tsx:18`, `LocksPanel.test.tsx`, `LocksPanel.tsx:15`, `Unable to find role="dialog" and name /Edit metadata/i`, `frontend/src/test/renderWithProviders.tsx`, `renderWithRouter.tsx`, `MediaManager.test.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
Error: useI18n must be used within I18nProvider
  at src/context/I18nContext.tsx:47
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-060"></a>

## ISS-060 – English settings catalogue contained Slovak workflow labels

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-060)

**Severity:** Medium (i18n UX)  
**Status:** ✅ Fixed (**2.0.47** / `f0a885c`)

### Operational synopsis

English workflow strings were copied from the Slovak catalogue. The English labels and OTP text were corrected.

### Evidence and traceability

- **Recorded versions:** `2.0.47`
- **Commit:** [`f0a885c`](https://github.com/techberode/paginiumcms-architecture/commit/f0a885c)
- **Key technical identifiers:** `frontend/src/i18n/modules/settings/en.ts`, `settings/en.ts`, `SettingsSchema.php`, `settings.test.ts`, `SettingsView.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-061"></a>

## ISS-061 – Audit messages remained Slovak in the English admin locale

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-061)

**Severity:** Medium (i18n UX)  
**Status:** ✅ Fixed (**2.0.49**)

### Operational synopsis

Audit formatting ignored the selected English locale. Message generation now applies the active translation catalogue.

### Evidence and traceability

- **Recorded versions:** `2.0.49`
- **Related incidents:** [ISS-048](#iss-048)
- **Key technical identifiers:** `/audit`, `Lang::getLocale()`, `formatAuditEvent.ts`, `backend/lang/{sk,en}/audit.php`, `Lang::get()`, `AuditTrailService::buildDiffMetadata()`, `audit.php`, `AuditMessageFormatter.php`, `AuditTrailService.php`, `EnhancedVersionManager.php`, `AuditTrail.tsx`, `DashboardActivityPanel.tsx`, `i18n/modules/audit/*`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-062"></a>

## ISS-062 – Public website contained hard-coded Slovak strings

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-062)

**Severity:** Medium (i18n UX)  
**Status:** ✅ Fixed (**2.0.50**)

### Operational synopsis

Public components contained literal Slovak text. Public-site strings now pass through the locale system.

### Evidence and traceability

- **Recorded versions:** `2.0.50`
- **Key technical identifiers:** `frontend/src/components/frontend/*`, `contentDates.ts`, `readingTime.ts`, `frontend/src/i18n/modules/public/{sk,en}.ts`, `public.test.ts`, `i18n/modules/public/*`, `BlogRenderer.tsx`, `Navbar.tsx`, `LoginModal.tsx`, `PublicSiteContext.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-063"></a>

## ISS-063 – Invalid date values crashed admin and public views

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-063)

**Severity:** High (prod crash)  
**Status:** ✅ **2.0.51**

### Operational synopsis

Invalid or missing date values reached Intl formatting and threw RangeError. Date parsing and fallback rendering were centralized and hardened.

### Evidence and traceability

- **Recorded versions:** `2.0.50`, `2.0.51`
- **Related incidents:** [ISS-062](#iss-062)
- **Key technical identifiers:** `contentDates.ts`, `VersionHistory.tsx`, `AuditTrail.tsx`, `PagesManager.tsx`, `LockIndicator.tsx`, `contentDates.test.ts`, `/pages/home`, `SiteSearchModal.tsx`, `PageRenderer.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
RangeError: Invalid time value
  at formatDistanceToNow … VersionHistory.tsx   ← admin /pages/:slug
  at toLocaleDateString … SiteSearchModal       ← verejný web
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-064"></a>

## ISS-064 – DEFAULT_LOCALE was not exported from i18n/index.ts

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-064)

**Severity:** Low (CI)  
**Status:** ✅ **2.0.51**

### Operational synopsis

The locale constant existed but was not exported through the public i18n barrel. The export was added and type-checking restored.

### Evidence and traceability

- **Recorded versions:** `2.0.51`
- **Related incidents:** [ISS-062](#iss-062)
- **Key technical identifiers:** `npm run type-check`, `../i18n`, `frontend/src/i18n/index.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
error TS2459: Module '"../i18n"' declares 'DEFAULT_LOCALE' locally, but it is not exported.
  src/utils/contentDates.ts(1,10)
  src/utils/readingTime.ts(1,10)
  src/utils/validation.ts(9,10)
```
```typescript
export { DEFAULT_LOCALE, type Locale, type MessageTree, type MessageValue } from './types';
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-065"></a>

## ISS-065 – Admin logs were two hours behind because PHP used UTC

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-065)

**Severity:** Medium (ops)  
**Status:** ✅ **2.0.51**

### Operational synopsis

PHP used UTC while the admin expected Europe/Bratislava. Application timezone configuration and display normalization were aligned.

### Evidence and traceability

- **Key technical identifiers:** `APP_TIMEZONE=Europe/Bratislava`, `.env`, `backend/bootstrap/timezone.php`, `APP_TIMEZONE`, `LocaleMiddleware`, `Europe/Bratislava`, `AppTimezone.php`, `bootstrap/timezone.php`, `LocaleMiddleware.php`, `SystemChecker.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-066"></a>

## ISS-066 – CronExpressionEvaluator same-minute and DST tests failed

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-066)

**Severity:** Low (CI)  
**Status:** ✅ **2.0.51**

### Operational synopsis

Cron due-state logic mishandled same-minute execution and daylight-saving transitions. Evaluator semantics and tests were corrected.

### Evidence and traceability

- **Key technical identifiers:** `./vendor/bin/phpunit`, `CronExpressionEvaluatorTest::testIsDueSinceLastRunSkipsSameMinute`, `Europe/Bratislava`, `CronExpressionEvaluator.php`, `CronExpressionEvaluatorTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-067"></a>

## ISS-067 – LocaleMiddleware test mock broke after timezone middleware

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-067)

**Severity:** Low (CI)  
**Status:** ✅ **2.0.51**

### Operational synopsis

Timezone middleware changed the request flow expected by a locale test mock. The test fixture was updated to match the real middleware chain.

### Evidence and traceability

- **Key technical identifiers:** `LocaleMiddlewareTest::testUsesConfiguredLanguageFromSettings`, `LocaleMiddlewareTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-068"></a>

## ISS-068 – Expected Code Policy rejection was logged as ERROR with stack trace

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-068)

**Severity:** Medium (logging)  
**Status:** ✅ **2.0.51**

### Operational synopsis

A user-facing policy rejection was treated as an application error. Logging severity now distinguishes expected validation denial from faults.

### Evidence and traceability

- **Key technical identifiers:** `CodeEditorControllerTest::testSaveFileRejectsPolicyViolation`, `CodeEditorManager`, `CodeEditorLogger.php`, `CodeEditorManager.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
Error in file backend/app/Modules/PolicyTest.php: Code policy validation failed
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-069"></a>

## ISS-069 – Timezone setting was only a free-text field

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-069)

**Severity:** Medium (admin UX)  
**Status:** ✅ **2.0.51**

### Operational synopsis

Free-text timezone input allowed invalid identifiers and poor usability. Settings now provide a searchable IANA timezone list.

### Evidence and traceability

- **Key technical identifiers:** `TimezoneSelect.tsx`, `utils/timezones.ts`, `Validator.php`, `SettingsSchema.php`, `timezones.ts`, `SettingsView.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-070"></a>

## ISS-070 – Settings lacked a daylight-saving-time switch

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-070)

**Severity:** Medium (ops)  
**Status:** ✅ **2.0.51**

### Operational synopsis

Operators could not choose fixed-standard-time behavior. A DST policy switch and runtime application logic were added.

### Evidence and traceability

- **Key technical identifiers:** `Europe/Bratislava`, `SettingsSchema.php`, `AppTimezone.php`, `TimezoneSelect.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-071"></a>

## ISS-071 – Logs lacked bulk actions, delete-all, and pagination

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-071)

**Severity:** Medium (admin UX)  
**Status:** ✅ Fixed · **2.0.51**

### Operational synopsis

Log management could not efficiently handle large datasets. Bulk archive/delete, delete-all, filters, and pagination were implemented.

### Evidence and traceability

- **Recorded versions:** `2.0.51`
- **Key technical identifiers:** `/logs`, `LogsManager`, `POST /api/admin/logs/bulk`, `POST /api/admin/logs/delete-all`, `LogControllerTest`, `ApplicationLogReader.php`, `LogController.php`, `logs.php`, `LogsManager.tsx`, `logs.ts`, `AdminListToolbar.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-072"></a>

## ISS-072 – Security audit endpoint returned 403 for ADMIN

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-072)

**Severity:** Medium (regression)  
**Status:** ✅ Fixed · **2.0.52**

### Operational synopsis

The security audit route required a permission stricter than the intended ADMIN role. Authorization rules were aligned with the documented role contract.

### Evidence and traceability

- **Key technical identifiers:** `GET /api/admin/security/audit`, `SecurityAuditControllerTest`, `/api/admin/security/*`, `backend/app/Http/Routes/security.php`, `GET /audit`, `GET /audit/export`, `GET/PUT /acl`, `./vendor/bin/phpunit --filter SecurityAuditControllerTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-073"></a>

## ISS-073 – Login tests returned 429 instead of 401 because lockouts persisted

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-073)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.0.52**

### Operational synopsis

Lockout state persisted across login tests, changing expected 401 responses into 429. Test isolation now clears the limiter state.

### Evidence and traceability

- **Related incidents:** [ISS-015](#iss-015)
- **Key technical identifiers:** `ApiResponseShapeTest::testLoginErrorShape`, `AuthControllerTest::testLoginWithNonExistentEmail`, `data/security/login_attempts.json`, `AuthController::login()`, `backend/tests/Http/TestCase.php`, `LoginAttemptTracker::clearAll()`, `./vendor/bin/phpunit`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-074"></a>

## ISS-074 – Access-control and branding changes introduced ten PHPStan errors

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-074)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.0.52**

### Operational synopsis

New access-control and branding shapes were insufficiently typed. Schemas, services, and tests were corrected for PHPStan level 8.

### Evidence and traceability

- **Related incidents:** [ISS-055](#iss-055)
- **Key technical identifiers:** `./vendor/bin/phpstan analyse --level=8 backend/app`, `AccessControlSyncService`, `SettingsSchema.php`, `AccessControlSyncService.php`, `PermissionCatalog.php`, `Http/Config/services.php`, `use AccessControlSyncService`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-075"></a>

## ISS-075 – PHPUnit crashed on duplicate HelloWidget Hooks class

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-075)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.0.54**

### Operational synopsis

A reference plugin class was loaded more than once in one PHPUnit process. Fixtures and loading guards now avoid duplicate declarations.

### Evidence and traceability

- **Key technical identifiers:** `PluginManagerTest::testEnableRegistersHooksOnBoot`, `PluginManager::loadPluginClasses()`, `PluginManagerTest.php`, `./vendor/bin/phpunit`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
PHP Fatal error: Cannot redeclare class PaginiumCMS\Http\Extensions\HelloWidget\Hooks
(previously declared in …/backend/app/Http/Extensions/hello-widget/src/Hooks.php:10)
in /tmp/pag_plugins_mgr_…/extensions/hello-widget/src/Hooks.php on line 7
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-076"></a>

## ISS-076 – passwordConfirm rollout triggered a cascade of 21 PHPUnit failures

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-076)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.0.56**

### Operational synopsis

The new password confirmation requirement invalidated many test payloads and cascaded into unrelated assertions. Shared fixtures and request shapes were updated.

### Evidence and traceability

- **Recorded versions:** `2.0.56`
- **Commit:** [`0664ba3`](https://github.com/techberode/paginiumcms-architecture/commit/0664ba3)
- **Key technical identifiers:** `GET /api/media`, `MediaControllerTest`, `OtpWorkflowServiceTest`, `AuthController::register`, `ValidationRules::validatePasswordConfirmation()`, `CoreHardeningTest.php`, `try/finally`, `TestCase.php`, `AuthControllerTest.php`, `./vendor/bin/phpunit`, `POST /api/auth/register`, `GET /api/auth/me`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
Failed asserting that 422 matches expected 403.
Failed asserting that 401 matches expected 200.
Failed asserting that null is not null.
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-077"></a>

## ISS-077 – Audit-trail CSV export bypassed LogSanitizer

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-077)

**Severity:** Medium (security)  
**Status:** ✅ Fixed · **2.1.0-beta.2**

### Operational synopsis

Audit CSV export did not pass every cell through the injection sanitizer. The export path now applies LogSanitizer consistently.

### Evidence and traceability

- **Key technical identifiers:** `AuditTrailService::exportAuditToCsv()`, `SecurityAuditStore::exportCsv()`, `LogSanitizer::value()`, `AuditTrailServiceTest::testExportAuditToCsvSanitizesAllCells()`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-078"></a>

## ISS-078 – react-router-dom advisories appeared after beta.2

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-078)

**Severity:** Medium (dependency)  
**Status:** ✅ Fixed · **2.1.0-beta.3**

### Operational synopsis

Dependency advisories arrived immediately after a beta tag. The router packages were upgraded together and the full frontend gate rerun.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.2`
- **Key technical identifiers:** `npm audit --audit-level=high`, `npm audit --audit-level=moderate`, `npm audit --audit-level=**high**`, `frontend/package.json`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-079"></a>

## ISS-079 – Blog profile rejected existing fenced code blocks on save

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-079)

**Severity:** High (admin UX)  
**Status:** ✅ Fixed · **2.1.0-beta.5**

### Operational synopsis

Code Policy interpreted an existing Markdown fence as unsafe executable code. The blog profile now permits valid fenced blocks while still rejecting scripts.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.5`
- **Key technical identifiers:** `EditorProfileService`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-080"></a>

## ISS-080 – ContentMetaController called a missing getGroup method

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-080)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.1.0-beta.4**

### Operational synopsis

The controller called a settings method that did not exist on the injected service. It now uses the supported group access contract.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.4`
- **Key technical identifiers:** `./scripts/iteration-gate.sh`, `POST /api/admin/content/suggest-meta`, `ContentMetaController.php`, `SettingsRepositoryInterface`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```text
Call to an undefined method SettingsRepositoryInterface::getGroup()
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-081"></a>

## ISS-081 – Partial @tiptap dependency update caused peer conflict and CI failure

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-081)

**Severity:** Medium (CI / deps)  
**Status:** ✅ Fixed · **2.1.0-beta.4**

### Operational synopsis

Dependabot updated only part of the tightly coupled TipTap family. Packages are now upgraded as one compatible set.

### Evidence and traceability

- **Recorded versions:** `2.8.3`
- **Related incidents:** [ISS-082](#iss-082), [ISS-083](#iss-083)
- **Key technical identifiers:** `@tiptap/extension-*`, `npm ci`, `@tiptap/extension-image@3.28.0`, `@tiptap/core@3.28.0`, `@tiptap/*`, `frontend/package.json`, `package-lock.json`, `rm package-lock.json && npm install`, `league/commonmark`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-082"></a>

## ISS-082 – symfony/yaml 8 upgrade was incompatible with the current constraint

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-082)

**Severity:** Low (technical debt)  
**Status:** ⏳ Deferred — major migrácia

### Operational synopsis

The suggested major Symfony YAML version exceeded the declared compatibility range. Migration is intentionally deferred to a dedicated major upgrade.

### Evidence and traceability

- **Key technical identifiers:** `symfony/yaml`, `composer.json`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-083"></a>

## ISS-083 – ESLint 10 required a breaking flat-config migration

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-083)

**Severity:** Low (technical debt)  
**Status:** ⏳ Deferred — samostatný upgrade

### Operational synopsis

ESLint 10 changes configuration and plugin compatibility. The upgrade is deferred until a controlled flat-config migration.

### Evidence and traceability

- **Related incidents:** [ISS-011](#iss-011)
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-084"></a>

## ISS-084 – Chrome sessions expired after roughly 24 minutes and caused 401 cascades

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-084)

**Severity:** High (auth UX)  
**Status:** ✅ Fixed · **2.1.0-beta.5**

### Operational synopsis

Session lifetime and keepalive behavior in Chrome caused a burst of unauthorized requests. Session refresh and frontend expiry handling were strengthened.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.5`
- **Related incidents:** [ISS-025](#iss-025), [ISS-029](#iss-029), [ISS-042](#iss-042)
- **Key technical identifiers:** `/login`, `GET /api/auth/me`, `GET /api/admin/counts`, `GET /api/media`, `GET /api/admin/users`, `DemoMode::sessionLifetimeSeconds()`, `SessionManager::refreshCookieLifetime()`, `SecureSessionManager::touch()`, `AuthMiddleware`, `.env`, `SESSION_LIFETIME=28800`, `backend/bootstrap/session.php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-085"></a>

## ISS-085 – Rich navigation icon rendered as an empty frame and hid description

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-085)

**Severity:** Medium (admin/public UX)  
**Status:** ✅ Fixed · **2.1.0-beta.5**

### Operational synopsis

Icon metadata and responsive rendering produced an empty frame and hid descriptions. Navigation rendering and fallbacks were corrected.

### Evidence and traceability

- **Key technical identifiers:** `navigationRich.ts`, `Navbar.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-086"></a>

## ISS-086 – Stored XSS survived strip_tags through dangerous attributes and schemes

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-086)

**Severity:** **Critical (security)**  
**Status:** ✅ Fixed · **2.1.0-beta.6**

### Operational synopsis

strip_tags removed elements but not dangerous attributes or URL schemes. A dedicated content sanitizer now removes event handlers and javascript/data payloads.

### Evidence and traceability

- **Key technical identifiers:** `ContentSecuritySanitizer::sanitizeHtml()`, `ContentSecuritySanitizer.php`, `frontend/src/utils/sanitizeHtml.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-087"></a>

## ISS-087 – LAN frontend deploy script contained hard-coded host, user, and port

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-087)

**Severity:** Medium (ops / hygiene)  
**Status:** ✅ Fixed · **2.1.0-beta.6**

### Operational synopsis

A deploy helper embedded environment-specific connection data. Host, user, port, and paths moved to explicit environment configuration.

### Evidence and traceability

- **Key technical identifiers:** `scripts/deploy-frontend-lan.sh`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
DEPLOY_HOST=192.168.x.x DEPLOY_USER=yourName DEPLOY_SSH_PORT=22 ./scripts/deploy-frontend-lan.sh
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-088"></a>

## ISS-088 – Backup import was vulnerable to Zip-Slip through extractTo

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-088)

**Severity:** Medium (security)  
**Status:** ✅ Fixed · **2.1.0-beta.6**

### Operational synopsis

Direct archive extraction allowed traversal entries to escape the restore directory. Entries are now validated before staged extraction.

### Evidence and traceability

- **Related incidents:** [ISS-086](#iss-086)
- **Key technical identifiers:** `BackupManager::importBackup()`, `ZipEntryGuard::isSafeEntry()`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-089"></a>

## ISS-089 – React Router RSC-only advisory was accepted as not reachable in the SPA

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-089)

**Severity:** Low (dependency — RSC path not used in SPA)  
**Status:** ✅ **2.1.0-beta.29** · `react-router-dom@7.18.2` + `react-router@7.18.2` override

### Operational synopsis

The advisory **GHSA-qwww-vcr4-c8h2** (*React Router: RSC Mode CSRF Bypass Allows Action Execution Before 400 Response*) applies only when React Router runs in **React Server Components (RSC) mode** with server actions. PaginiumCMS uses a **React 18 client-side SPA** (Vite build, no RSC pipeline) — the vulnerable code path was never reachable.

**Resolution (2.1.0-beta.29):** bump `react-router-dom` and the `react-router` npm override to **7.18.2** (patched upstream). Removes `npm audit` high findings without `npm audit fix --force`; `auditConfig.ignore` for this GHSA removed.

### Evidence and traceability

- **Advisory:** [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2)
- **Related incidents:** [ISS-078](#iss-078), [ISS-083](#iss-083), [ISS-117](#iss-117)
- **Key technical identifiers:** `frontend/package.json` (`react-router-dom@7.18.2`, override `react-router@7.18.2`)
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-29)

### Verification or operational excerpts

```bash
cd frontend && npm audit
# Expected: found 0 vulnerabilities

cd frontend && npm audit --audit-level=high
# Expected: exit 0

cd frontend && npm run type-check && npm test
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-090"></a>

## ISS-090 – eslint latest and npm audit fix caused dependency resolution failure

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-090)

**Severity:** Low (CI/deps)  
**Status:** ✅ Fixed · **2.1.0-beta.7**

### Operational synopsis

An unbounded latest dependency and forced audit fix created peer conflicts. Versions and overrides were returned to a compatible set.

### Evidence and traceability

- **Key technical identifiers:** `npm audit fix`, `@eslint/js@^9.39.0`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-091"></a>

## ISS-091 – React Router override and useOptimistic caused fourteen Vitest failures

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-091)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · **2.1.0-beta.7**

### Operational synopsis

A router override installed an incompatible major version and exposed unsupported React APIs. Removing the override restored the test matrix.


> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-092"></a>

## ISS-092 – Deploy script mixed local environment assumptions with invalid :? syntax

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-092)

**Severity:** Low (ops)  
**Status:** ✅ Fixed · **2.1.0-beta.7**

### Operational synopsis

The deploy script depended on local-only variables and used invalid parameter expansion. Environment loading and shell validation were corrected.

### Evidence and traceability

- **Key technical identifiers:** `scripts/deploy-frontend-lan.env.local`, `.env.example`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-093"></a>

## ISS-093 – brace-expansion override broke ESLint with expand is not a function

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-093)

**Severity:** Medium (CI)  
**Status:** ✅ Fixed · odstránený override

### Operational synopsis

Forcing brace-expansion 5 beneath minimatch 3 broke ESLint internals. The incompatible override was removed.

### Evidence and traceability

- **Related incidents:** [ISS-083](#iss-083), [ISS-089](#iss-089)
- **Key technical identifiers:** `npm run lint`, `@eslint/config-array`, `package.json`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-094"></a>

## ISS-094 – Production job scheduler run endpoint returned HTTP 500

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-094)

**Severity:** High (prod)  
**Status:** ✅ Fixed · **It.62** (`f7a73f1`)

### Operational synopsis

The scheduler API could not write its Docker-backed storage and the UI obscured the failure. Storage permissions, job execution, and reporting were repaired.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.9`
- **Commit:** [`0fe21ec`](https://github.com/techberode/paginiumcms-architecture/commit/0fe21ec)
- **Commit:** [`f7a73f1`](https://github.com/techberode/paginiumcms-architecture/commit/f7a73f1)
- **Key technical identifiers:** `POST /api/admin/jobs/{id}/run`, `backend/storage/app/content/data/jobs/runs.json`, `scheduler-state.json`, `LogStoragePaths.php`, `composer.json`, `ScheduledJobRunner::finalizeRun()`, `docker/php/php.ini`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
./stack.sh exec -u www-data php sh -c 'touch .../data/jobs/.write-test && rm ... && echo WRITE_OK'
./stack.sh exec php php backend/bin/console jobs:run backup-scheduled
# Admin /scheduler — zelený toast „Backup not due“, badge Preskočené v histórii
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-095"></a>

## ISS-095 – Maintenance heroImageUrl rejected valid /storage/ paths

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-095)

**Severity:** Medium (admin UX)  
**Status:** ✅ Fixed · **main `88cbe31`**

### Operational synopsis

Validation accepted only external URLs and rejected internal media paths. Maintenance settings now accept safe /storage/ references.

### Evidence and traceability

- **Commit:** [`88cbe31`](https://github.com/techberode/paginiumcms-architecture/commit/88cbe31)
- **Key technical identifiers:** `/storage/app/content/media/…`, `https://…`, `/storage/`, `SettingsRepositoryTest::testMaintenanceHeroImageUrlAcceptsStoragePath`, `npm run build:prod`, `./stack.sh restart php`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-096"></a>

## ISS-096 – Temporary 502 occurred immediately after restarting the PHP container

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-096)

**Severity:** Low (ops)  
**Status:** ℹ️ Informational — počkať 5–10 s

### Operational synopsis

nginx briefly reached PHP while the container was restarting. Deployment now treats this as a bounded readiness interval rather than a product defect.

### Evidence and traceability

- **Key technical identifiers:** `./stack.sh restart php`, `/api/health`, `./stack.sh logs --tail=50 php`, `GET /api/pages`, `/api/navigation`, `GET /api/auth/me`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
cd /var/lib/docker/compose/paginiumcms
./stack.sh ps          # php = Up
curl -s http://127.0.0.1:8089/api/health
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-097"></a>

## ISS-097 – Newsletter subscribers had no administration interface

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-097)

**Severity:** Medium  
**Status:** ✅ Fixed · **It.61**

### Operational synopsis

Subscription data existed but administrators could not inspect or manage it. Iteration 61 added the subscriber administration workflow.

### Evidence and traceability

- **Key technical identifiers:** `POST /api/maintenance/newsletter`, `data/newsletter/subscribers.json`, `NewsletterRepository::findAll()`, `POST /api/newsletter/subscribe`, `/newsletter`, `GET /api/admin/newsletter/subscribers`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
# APP_ROOT = /var/www/paginiumcms.com
cat backend/storage/app/content/data/newsletter/subscribers.json | jq .
# alebo v Dockeri:
./stack.sh exec php cat /var/www/html/backend/storage/app/content/data/newsletter/subscribers.json
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-098"></a>

## ISS-098 – Demo login returned empty 401 responses because of CORS and APP_URL

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-098)

**Severity:** **High (demo)**  
**Status:** ✅ Fixed · **SameOriginCors** + `.env`

### Operational synopsis

Frontend origin and APP_URL disagreed, producing an empty CORS failure. Same-origin CORS handling and environment validation fixed demo login.

### Evidence and traceability

- **Key technical identifiers:** `POST /api/auth/login`, `Content-Type: text/html`, `Origin: https://demo.paginiumcms.com`, `CorsMiddleware`, `.env`, `APP_URL=https://paginiumcms.com`, `SameOriginCorsMiddleware`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
grep '^APP_URL=' /var/www/paginiumcms-demo/.env

curl -sS -o /dev/null -w 'bez Origin: HTTP %{http_code}\n' \
  -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'

curl -sS -o /dev/null -w 's Origin:    HTTP %{http_code} CT:%{content_type}\n' \
  -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://demo.paginiumcms.com' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'
# Ak prvá 200 a druhá 401 text/html → ISS-098 potvrdené
```
```bash
sed -i 's|^APP_URL=.*|APP_URL=https://demo.paginiumcms.com|' /var/www/paginiumcms-demo/.env
cd /var/lib/docker/compose/paginiumcms-demo && ./stack.sh up -d
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-099"></a>

## ISS-099 – Demo reset CLI lacked permission to update plugins.json

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-099)

**Severity:** Medium (demo ops)  
**Status:** ℹ️ Ops — storage `chown user:www-data`, dirs `2775`

### Operational synopsis

The cron identity lacked group write access to demo storage. Shared group ownership, setgid directories, and corrected permissions resolve the operation.

### Evidence and traceability

- **Related incidents:** [ISS-094](#iss-094)
- **Key technical identifiers:** `php backend/bin/console demo:reset-if-due`, `PluginManager`, `data/plugins.json`, `runs.json`, `./stack.sh exec -T php php backend/bin/console demo:reset-if-due`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```
fopen(.../storage/app/demo/data/plugins.json): Permission denied
RuntimeException: Unable to open plugin registry
```
```bash
ls -la /var/www/paginiumcms-demo/backend/storage/app/demo/data/
id -un
cd /var/www/paginiumcms-demo && php backend/bin/console demo:reset-if-due
```
```bash
cd /var/www/paginiumcms-demo
sudo chown -R "$(id -un):www-data" backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 664 {} \;
php backend/bin/console demo:reset-if-due
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-100"></a>

## ISS-100 – Public settings exposed the demo password

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-100)

**Severity:** Medium (audit)  
**Status:** ✅ **`v2.1.0-beta.11`** — quick-login, no password in GET

### Operational synopsis

A public settings response exposed reusable demo credentials. Quick-login now performs a server-side action without returning the password.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.11`
- **Key technical identifiers:** `curl …/api/settings/public`, `POST /api/demo/quick-login`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
curl -s https://demo.paginiumcms.com/api/settings/public | jq '.data.demo'
# žiadny kľúč credentials / password
curl -sS -X POST https://demo.paginiumcms.com/api/demo/quick-login | jq '.success'
# true
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-101"></a>

## ISS-101 – Editor crashed because capabilities was not normalized to an array

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-101)

**Severity:** High (demo/admin)  
**Status:** ✅ **`v2.1.0-beta.11`** — normalize API profile shape

### Operational synopsis

The editor assumed capabilities was always an array. API profile normalization now guarantees a safe frontend shape.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.11`
- **Key technical identifiers:** `/api/settings/public`, `frontend/src/utils/editorProfiles.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-102"></a>

## ISS-102 – Demo API returned HTTP 500 because the demo data tree could not be created

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-102)

**Severity:** **High (demo)**  
**Status:** ✅ Ops — storage bootstrap (2026-07-27)

### Operational synopsis

The demo runtime could not create required directories under its storage owner. A one-time storage bootstrap establishes the full tree and permissions.

### Evidence and traceability

- **Related incidents:** [ISS-099](#iss-099), [ISS-094](#iss-094)
- **Key technical identifiers:** `/api/health`, `demo/data missing`, `$APP_ROOT/storage/app/demo/...`, `backend/storage/app/demo/data/`, `data/`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```json
{ "success": false, "error": "Vnútorná chyba servera" }
```
```
PluginRegistry.php: Unable to open plugin registry: .../backend/storage/app/demo/data/plugins.json
FirewallBanStore.php: mkdir(): Permission denied
FirewallBanStore.php: Failed to open stream: .../whitelist.json: No such file or directory
```
```bash
APP_ROOT=/var/www/paginiumcms-demo
STORAGE="$APP_ROOT/backend/storage"

sudo mkdir -p "$STORAGE/app/demo/data" "$STORAGE/app/demo/data/security/firewall" \
  "$STORAGE/cache" "$STORAGE/backups"
sudo chown -R "$(id -un):www-data" "$STORAGE"
sudo find "$STORAGE" -type d -exec chmod 2775 {} \;
sudo find "$STORAGE" -type f -exec chmod 664 {} \;
# minimálne JSON pre bootstrap — detail v DEMO_DEPLOY.md § First-run
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-103"></a>

## ISS-103 – Local DEMO_MODE polluted PHPUnit OTP and 2FA tests

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-103)

**Severity:** Medium (dev/CI)  
**Status:** ✅ **`v2.1.0-beta.12`** — test bootstrap izolácia

### Operational synopsis

The local .env leaked DEMO_MODE into HTTP tests. Testing bootstrap now overrides environment state and isolates demo configuration.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.12`
- **Key technical identifiers:** `./scripts/run-all-tests.zsh`, `AuthControllerTest`, `CommentsControllerTest`, `.env`, `bootstrap/app.php`, `APP_ENV=testing`, `backend/tests/Http/TestCase.php`, `CsrfMiddleware`, `/api/auth/register/verify-otp`, `…/resend-otp`, `APP_ENV`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-104"></a>

## ISS-104 – ADMIN could bypass SUPER_ADMIN through the system-deploy jobs API

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-104)

**Severity:** Medium (audit)  
**Status:** ✅ **`v2.1.0-beta.15`**

### Operational synopsis

Generic job execution authorization allowed ADMIN to trigger a SUPER_ADMIN deployment job. The system-deploy action now performs dedicated authorization.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.15`
- **Key technical identifiers:** `/api/admin/system/update/run`, `PUT /api/admin/jobs/system-deploy`, `POST /api/admin/jobs/system-deploy/run`, `JobsController`, `ScheduledJobRunner::runDue()`, `JobsControllerPrivilegedDeployTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-105"></a>

## ISS-105 – GeoIP lookup used cleartext HTTP without OutboundUrlGuard

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-105)

**Severity:** Low (audit)  
**Status:** ✅ **`v2.1.0-beta.15`**

### Operational synopsis

GeoIP used an unencrypted external endpoint and bypassed outbound policy. The integration now uses the guarded HTTPS path.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.15`
- **Key technical identifiers:** `GeoIPService`, `http://ip-api.com`, `OutboundUrlGuard`, `OutboundUrlGuard::isAllowed()`, `GeoIPServiceTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-106"></a>

## ISS-106 – DEMO_MODE could be enabled in production without failing closed

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-106)

**Severity:** Low (audit)  
**Status:** ✅ **`v2.1.0-beta.16`**

### Operational synopsis

A production instance could accidentally start in demo mode. Startup validation now fails closed when DEMO_MODE conflicts with production.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.16`
- **Key technical identifiers:** `APP_ENV=production`, `DemoMode::isEnabledFromEnv()`, `DemoControllerTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-107"></a>

## ISS-107 – Maintenance newsletter subscription lacked honeypot and dedicated rate limit

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-107)

**Severity:** Low (audit)  
**Status:** ✅ **`v2.1.0-beta.16`**

### Operational synopsis

The anonymous maintenance subscription endpoint lacked anti-bot and workflow-specific throttling. Honeypot and dedicated limits were added.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.16`
- **Key technical identifiers:** `/api/maintenance/newsletter`, `NewsletterSubscribeRateLimitMiddleware`, `NewsletterControllerTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-108"></a>

## ISS-108 – GitHubService curl calls bypassed OutboundUrlGuard

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-108)

**Severity:** Info (audit)  
**Status:** ✅ **`v2.1.0-beta.16`**

### Operational synopsis

GitHubService performed direct curl requests outside centralized SSRF controls. Requests now pass through OutboundUrlGuard policy.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.16`
- **Related incidents:** [ISS-054](#iss-054)
- **Key technical identifiers:** `GitHubService::apiRequest()`, `OutboundUrlGuard`, `OutboundUrlGuard::fromEnv()->assertAllowed($url)`, `GitHubServiceTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-109"></a>

## ISS-109 – Newsletter footer call-to-action was too large

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-109)

**Severity:** Low (UX)  
**Status:** ✅ **`v2.1.0-beta.18`**

### Operational synopsis

The footer subscription block dominated the layout. Responsive spacing and content density were reduced.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.18`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-110"></a>

## ISS-110 – Production SEO endpoint returned 500 due to cache shape collision

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-110)

**Severity:** **High (prod)**  
**Status:** ✅ **`v2.1.0-beta.21`**

### Operational synopsis

A cached array was treated as a Content object in SEO serialization. Cache normalization now preserves the expected domain shape.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.21`
- **Key technical identifiers:** `GET /api/seo/page/home 500`, `GET /api/seo/article/… 500`, `ContentController`, `SeoController`, `ContentRepository`, `ContentCacheService`, `GET /api/seo/page/home`, `GET /api/pages/home`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-111"></a>

## ISS-111 – LoggerTest and PHPStan regressed after testing-environment log suppression

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-111)

**Severity:** Medium (CI/tests)  
**Status:** ✅ **`v2.1.0-beta.21`**

### Operational synopsis

Suppressing logs in testing changed test assumptions and exposed static type issues. Logger behavior and tests were realigned.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.21`
- **Key technical identifiers:** `AccessLogServiceTest.php:59`, `APP_ENV=testing`, `FileHelper::readJson()`, `Logger::isTestingEnvironment()`, `AccessLogServiceTest::readEntries()`, `array_values(FileHelper::readJson(...))`, `AccessLogServiceTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-112"></a>

## ISS-112 – Lock badge displayed activity more than 56 years ago

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-112)

**Severity:** Low (admin UX)  
**Status:** ✅ fixed lokálne — **next release**

### Operational synopsis

The API returned Unix seconds while the UI interpreted milliseconds. Timestamp normalization now converts units before relative-time formatting.

### Evidence and traceability

- **Recorded versions:** `v2.1.0-beta.23`
- **Key technical identifiers:** `/api/locks/*`, `frontend/src/utils/contentDates.ts`, `contentDates.test.ts`, `npx vitest run src/utils/contentDates.test.ts`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```ts
// BUG: value * 1000 > 1e12 — pre každý timestamp po ~2001 (sekundy)
// vyhodnotí true → Date(seconds) ako ms → ~1970 → „56 rokov“
new Date(value * 1000 > 1_000_000_000_000 ? value : value * 1000)
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-113"></a>

## ISS-113 – Static SPA responses lacked security headers

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-113)

**Severity:** Medium (audit)  
**Status:** ✅ nginx snippet + prod/demo template

### Operational synopsis

PHP middleware protected API responses but not files served directly by nginx. Shared HTTP/HTTPS security-header snippets were added to static routes.

### Evidence and traceability

- **Key technical identifiers:** `SecurityMiddleware`, `/api/*`, `frontend/dist`, `location /`, `/assets/`, `docs/deploy/nginx-security-headers-https.conf`, `docker/nginx/security-headers.conf`, `/etc/nginx/sites-enabled/paginiumcms`, `/.well-known/security.txt`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
sudo cp docs/deploy/nginx-security-headers-https.conf /etc/nginx/snippets/paginium-security-headers-https.conf
# Merge includes from docs/deploy/nginx-paginiumcms.com.conf into the ACTIVE vhost (see prod note below)
sudo nginx -t && sudo systemctl reload nginx
curl -sI https://paginiumcms.com/ | grep -iE 'strict-transport|content-security|x-frame'
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-114"></a>

## ISS-114 – CSRF exemption prefix lacked a slash boundary

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-114)

**Severity:** Medium (audit)  
**Status:** ✅ `CsrfMiddleware::isExempt()`

### Operational synopsis

Prefix matching could exempt unintended paths such as a longer look-alike route. CSRF exemption now requires an exact path or slash-delimited child.

### Evidence and traceability

- **Key technical identifiers:** `$path === $prefix || str_starts_with($path, $prefix . '/')`, `CsrfMiddleware::isExempt()`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-115"></a>

## ISS-115 – expose_php disclosed the PHP version

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-115)

**Severity:** Low (audit)  
**Status:** ✅ `docker/php/php.ini`

### Operational synopsis

The default PHP header revealed the runtime version. expose_php is disabled in the container php.ini.

### Evidence and traceability

- **Key technical identifiers:** `expose_php = Off`, `docker/php/php.ini`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-116"></a>

## ISS-116 – TRUSTED_PROXIES default contained a hard-coded LAN address

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-116)

**Severity:** Low (audit)  
**Status:** ✅ default `127.0.0.1,::1` + `.env`

### Operational synopsis

A project-specific LAN proxy address was shipped as a default. Only loopback is trusted by default and deployments opt in explicitly.

### Evidence and traceability

- **Key technical identifiers:** `127.0.0.1,::1`, `ClientIpResolver::trustedProxiesFromEnv()`, `bootstrap/app.php`, `TRUSTED_PROXIES`, `.env`, `.env.example`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-117"></a>

## ISS-117 – React Router RSC advisory was not applicable to the SPA profile

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-117)

**Severity:** Low (dependency)  
**Status:** ✅ **2.1.0-beta.29** · see [ISS-089](#iss-089)

### Operational synopsis

Same as [ISS-089](#iss-089): **GHSA-qwww-vcr4-c8h2** patched in **`react-router-dom@7.18.2`**; RSC-mode CSRF bypass was never reachable in the PaginiumCMS SPA profile.

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-118"></a>

## ISS-118 – security.txt was missing or swallowed by SPA fallback

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-118)

**Severity:** Low (audit)  
**Status:** ✅ `frontend/public/.well-known/` + nginx

### Operational synopsis

The standard disclosure file was absent or routed to index.html. A static file and nginx exception now serve the correct resource.

### Evidence and traceability

- **Key technical identifiers:** `frontend/public/.well-known/security.txt`, `backend/public/`, `location = /.well-known/security.txt`, `Content-Type: text/plain`
- **History:** [CHANGELOG](../CHANGELOG.md)

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-119"></a>

## ISS-119 – Docker stack did not restart after host reboot

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-119)

**Severity:** Medium (ops)  
**Status:** ✅ `restart: unless-stopped` v prod compose

### Operational synopsis

Compose services lacked a restart policy. Production now uses restart: unless-stopped and documents boot verification.

### Evidence and traceability

- **Key technical identifiers:** `./stack.sh up -d`, `docs/deploy/docker-compose.prod.yml`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
docker inspect --format '{{.Name}} restart={{.HostConfig.RestartPolicy.Name}}' $(docker ps -aq --filter name=paginium)
```

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-120"></a>

## ISS-120 – CI PHPUnit output exposed TOTP and 2FA secrets in GitHub job logs

[↑ Overview](#overview) · [Slovak detailed record](sk/ISSUES.md#iss-120)

**Severity:** Medium (security / CI)  
**Status:** ✅ sanitize wrapper + verify

### Operational synopsis

Verbose 2FA tests printed secrets, QR payloads, provisioning URIs, and OTP values. CI now captures raw output privately, sanitizes it, verifies redaction, and publishes only safe logs.

### Evidence and traceability

- **Recorded versions:** `2.0.52`, `2.1.0-beta.7`
- **Related incidents:** [ISS-079](#iss-079), [ISS-085](#iss-085), [ISS-080](#iss-080), [ISS-094](#iss-094), [ISS-097](#iss-097), [ISS-109](#iss-109), [ISS-098](#iss-098), [ISS-099](#iss-099), [ISS-102](#iss-102), [ISS-038](#iss-038), [ISS-013](#iss-013)
- **Key technical identifiers:** `./vendor/bin/phpunit`, `otpauth://`, `.github/scripts/run-backend-tests-ci.sh`, `.github/scripts/sanitize-ci-log.py`, `.github/scripts/verify-ci-log-redaction.sh`, `.github/workflows/ci.yml`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
.github/scripts/run-backend-tests-ci.sh
# očakávané: „CI log redaction verification: OK“, v konzole len [REDACTED] namiesto otpauth
```

### Historical GitHub Actions logs (pre-`ee19806`)

Commit **`ee19806`** introduced sanitized PHPUnit output before publishing CI logs ([ISS-120](#iss-120)). **Workflow runs completed before that commit may still contain raw TOTP/2FA material in retained GitHub job logs.**

**Manual hygiene (ops — not automatable from the repo):**

1. GitHub → repository → **Actions** → filter workflow **CI**.
2. For runs **before** `2026-07-27` / commit `ee19806`, use **Delete all logs** (or delete individual runs) if logs were ever public or shared.
3. After cleanup, spot-check a recent run: secrets must appear only as `[REDACTED]` in the published PHPUnit step.

> Retention policy: prefer **90 days or less** for Actions log retention in repository settings when available.

> The linked Slovak record preserves the complete symptom, root-cause analysis, implementation detail, and verification narrative from the supplied source.


---

<a id="iss-121"></a>

## ISS-121 – Invalid settings group shapes were silently dropped during normalization

[↑ Overview](#overview)

**Severity:** Medium (data integrity)  
**Status:** ✅ **2.1.0-beta.28** · Iteration 68 fail-closed validation  
**Iteration:** [It.68](en/ITERATION_68.md)

### Operational synopsis

`SettingsRepository::normalizeOverrides()` skipped non-array group values (for example `"general": "not-an-object"`). Corrupt `settings.json` could be read as if the group did not exist, and a later partial write could persist without surfacing the integrity problem. It.68 validates the full overrides document before and after mutation and returns HTTP **422** for invalid shapes.

### Evidence and traceability

- **Key technical identifiers:** `SettingsRepository::normalizeOverrides()`, `DocumentValidator`, `DocumentSchemaRegistry::TYPE_SETTINGS_OVERRIDES`, `SettingsControllerEngineTest`, `SettingsStorageParityTest`
- **History:** [CHANGELOG](../CHANGELOG.md) · [ITERATION_68](en/ITERATION_68.md)

### Verification or operational excerpts

```bash
./vendor/bin/phpunit backend/tests/Core/Settings/SettingsStorageParityTest.php
./vendor/bin/phpunit backend/tests/Http/Controllers/Admin/SettingsControllerEngineTest.php
```


---

<a id="iss-122"></a>

## ISS-122 – Storage read path did not enforce base-path containment (symlink escape)

[↑ Overview](#overview)

**Severity:** Medium (security)  
**Status:** ✅ **2.1.0-beta.28** · Iteration 68 storage driver  
**Iteration:** [It.68](en/ITERATION_68.md)

### Operational synopsis

The initial It.68 `LocalFlatFileStorage` implementation called `assertWithinBase()` only on write and `resolveAbsolutePath()`. `read()`, `exists()`, `delete()`, and `list()` delegated directly to `FileReader`/`FileWriter`, so a symlink under the storage root could expose files outside the configured base. All public storage methods now enforce the same containment rules.

### Evidence and traceability

- **Key technical identifiers:** `LocalFlatFileStorage::assertWithinBase()`, `LocalFlatFileStorageTest::testRejectsSymlinkEscape`
- **History:** [CHANGELOG](../CHANGELOG.md) · [STORAGE](en/architecture/STORAGE.md)

### Verification or operational excerpts

```bash
./vendor/bin/phpunit backend/tests/Core/Storage/LocalFlatFileStorageTest.php
```


---

<a id="iss-123"></a>

## ISS-123 – Corrupt `settings.testing.json` leaked between HTTP PHPUnit tests

[↑ Overview](#overview)

**Severity:** Low (tests / CI hygiene)  
**Status:** ✅ **2.1.0-beta.28** · HTTP test harness reset  
**Iteration:** [It.68](en/ITERATION_68.md)

### Operational synopsis

`SettingsControllerEngineTest` wrote an intentionally corrupt `data/settings.testing.json` to assert API **422** behavior. Because later tests reused the same file, `Http\TestCase::applyTestSettingsOverrides()` failed during `setUp()` with `ValidationException`. The HTTP test base now deletes the testing settings file before applying overrides; the corrupt-state test **deletes** the file in a `finally` block (instead of writing `{}`).

### Evidence and traceability

- **Key technical identifiers:** `PaginiumCMS\Tests\Http\TestCase::applyTestSettingsOverrides()`, `SettingsControllerEngineTest`
- **History:** [CHANGELOG](../CHANGELOG.md)

### Verification or operational excerpts

```bash
./vendor/bin/phpunit backend/tests/Http/Controllers/Admin/SettingsControllerEngineTest.php
```

---

<a id="iss-124"></a>

## ISS-124 – CSP retains `style-src 'unsafe-inline'` for React inline styles

[↑ Overview](#overview)

**Severity:** Low (documented residual risk)  
**Status:** ℹ️ Accepted · It.67 CSP inventory  
**Iteration:** [It.67](en/ITERATION_67.md)

### Operational synopsis

`SecurityMiddleware` ships without `script-src 'unsafe-inline'` (XSS hardening). `style-src 'unsafe-inline'` remains required for React inline `style={{…}}` attributes and some third-party components. It.67 added `frame-ancestors 'none'`, `base-uri 'self'`, and `form-action 'self'` to the active middleware CSP. Tightening `style-src` requires a UI regression pass across admin and public surfaces.

### Evidence and traceability

- **Key technical identifiers:** `SecurityMiddleware`, `docs/deploy/NGINX_API.md`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · It.67

### Verification or operational excerpts

```bash
curl -sI http://localhost:8080/api/settings/public | grep -i content-security-policy
# Expect: script-src 'self' (no unsafe-inline); style-src 'self' 'unsafe-inline'
```

---

<a id="iss-125"></a>

## ISS-125 – DEMO_MODE leaked into HTTP PHPUnit after demo tests (four failures post-beta.27)

[↑ Overview](#overview)

**Severity:** Medium (CI)  
**Status:** ✅ **2.1.0-beta.28** · PHPUnit / HTTP test env hardening  
**Related:** [ISS-103](#iss-103) (original local `.env` pollution, beta.12)

### Operational synopsis

After **`v2.1.0-beta.27`**, GitHub Actions reported four PHPUnit failures that passed locally in isolation but failed in the full suite:

| Test | Expected | Got | Symptom |
|------|----------|-----|---------|
| `AuthControllerTest::testRegisterWithOtpEnabled` | 201 (verify OTP) | 403 | OTP registration reported disabled at verify step |
| `CommentsControllerTest::testApproveCommentWithOtpEnabled` | 202 (OTP challenge) | 200 | Comment approved without OTP |
| `SystemUpdateControllerTest::testRunForbiddenWhenDeployDisabled` | 403 (deploy off) | 401 | Super-admin not authenticated |
| `SystemUpdateControllerTest::testRunQueuesJobWhenEnabled` | 200 (queued) | 403 | Demo instance blocked system update |

All four symptoms match **`DEMO_MODE=true`** leaking from demo-focused tests (`DemoControllerTest`, `DemoLoginIsolationTest`, `DemoStorageQuotaServiceTest`) into subsequent HTTP tests: `DemoLoginGuard` blocks non-demo logins (401), `SystemUpdateController` and `SystemDeployTriggerService` fail-closed on demo (403), and workflow OTP flags read as disabled when storage/bootstrap context is inconsistent.

### Root cause

Demo tests mutated `putenv('DEMO_MODE=true')` and sometimes re-bootstrapped `$this->app` without guaranteed restore before the next test. `Http/TestCase` reset env in `setUp`/`tearDown`, but **`phpunit.xml` did not force `DEMO_MODE=false`**, and **`$_SERVER['DEMO_MODE']` was unset rather than set to `false`**. New `DemoStorageQuotaServiceTest` (post-beta.27) set demo env without `tearDown` cleanup if an assertion failed mid-test.

### Fix

- `phpunit.xml`: `<env name="DEMO_MODE" value="false" force="true"/>`
- `tests/bootstrap.php`: reset `APP_ENV` / `DEMO_MODE` before autoload
- `Http/TestCase`: sync `$_SERVER['DEMO_MODE']`, purge `data/otp-challenges.json`, add `enableWorkflows()` helper
- Demo HTTP tests: `try/finally` + re-bootstrap after env mutation
- `DemoStorageQuotaServiceTest`: env restore in `tearDown`
- OTP / SystemUpdate tests: assert workflow flags and login **200** before endpoint assertions
- `SettingsControllerEngineTest`: delete corrupt settings file in `finally` (see [ISS-123](#iss-123))

### Evidence and traceability

- **Key technical identifiers:** `DemoLoginGuard`, `SystemUpdateController::run()`, `OtpWorkflowService`, `DemoMode::isEnabledFromEnv()`, `backend/tests/Http/TestCase.php`, `phpunit.xml`, `tests/bootstrap.php`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28)

### Verification or operational excerpts

```bash
./scripts/iteration-gate.sh
DEMO_MODE=true ./vendor/bin/phpunit --filter 'testRegisterWithOtpEnabled|testRunQueuesJobWhenEnabled'
```

---

<a id="iss-126"></a>

## ISS-126 – Post-beta.27 CI regressions (storage path, void mock, API barrel, demo disk metric)

[↑ Overview](#overview)

**Severity:** Medium (CI / demo UX)  
**Status:** ✅ **2.1.0-beta.28** · hotfix commits included in release tag  
**Release context:** Bundled into **`v2.1.0-beta.28`** (post-beta.27 hotfixes on `main`).

### Operational synopsis

CI and demo ops issues discovered immediately after the beta.27 tag:

1. **`LocalFlatFileStorage::assertWithinBase()`** — logical paths such as `data/settings.testing.json` failed when intermediate directories did not exist yet (fresh CI checkout). Fixed to treat a missing base directory like a path outside base (allow first write).
2. **`ScheduledJobRunnerTest`** — PHPUnit mock declared `createDirectory(): bool` but production `FileWriter::createDirectory()` is **`void`**; CI failed with incompatible return type. Mock updated to `willReturn(null)`.
3. **Frontend API barrel (It.17)** — `git`, `shortcodes`, and `themes` clients were implemented but not exported from `frontend/src/api/index.ts`; `npm run lint:api-barrel` failed in CI.
4. **Demo dashboard disk metric** — `DEMO_MODE=true` instances displayed the **host partition** free space on the admin dashboard instead of a sandbox quota. `DemoStorageQuotaService` now reports synthetic quota (default **2 GiB** from `DEMO_STORAGE_QUOTA_BYTES`) based on `storage/app/demo/` usage only.

### Evidence and traceability

- **Key technical identifiers:** `LocalFlatFileStorage`, `ScheduledJobRunnerTest`, `frontend/src/api/index.ts`, `DemoStorageQuotaService`, `StorageChecker`, `DashboardController`
- **Commits:** `732ee44` (storage + barrel), `b7c13dd` (demo quota + related)
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28)

### Verification or operational excerpts

```bash
./scripts/iteration-gate.sh
./vendor/bin/phpunit backend/tests/Core/Scheduler/Services/ScheduledJobRunnerTest.php
cd frontend && npm run lint:api-barrel
```

### Deploy note

Apply with **`GIT_REF=origin/main`** on production and demo — **no new tag**. See [PRIVATE_DOMAIN_DEPLOY.md](../PRIVATE_DOMAIN_DEPLOY.md) or `scripts/deploy-instance-update.sh`.

<a id="iss-127"></a>

## ISS-127 – It.71 Performance Guard DI incomplete — 233 PHPUnit errors, console fatal

[↑ Overview](#overview)

**Severity:** High (CI / bootstrap)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** Discovered in local **`alltests`** run after It.71 implementation (Performance Guard / APM).

### Operational synopsis

Adding It.71 registered `MetricsController` and Performance Guard services in `Core/Performance/Config/services.php` using bare `create(Class::class)` entries. The project DI container does **not** autowire constructor parameters — every dependency must be explicit (same pattern as `Monitoring/Config/services.php` and `Http/Config/services.php`).

At route bootstrap, `metrics.php` resolves `MetricsController` immediately → **PHP-DI `InvalidDefinition`** on parameter `$settings` (`PerformanceGuardSettings`). This cascaded into **233 HTTP PHPUnit errors**, security-regression subset failures, path ACL subset failures, OTP subset failures, and **`backend/bin/console` fatal** (content diagnose step).

Secondary issues in the same bundle:

1. **`permissionsAdmin` schema default** omitted `metrics:read` (and `git:publish`) while `PermissionCatalog` included them — settings-backed ACL overrides catalog defaults → ADMIN received **403** on `GET /api/admin/metrics/apm`.
2. **`PerformanceRouteLabelResolver`** called `RouteContext::fromRequest()` without a matched route (unit tests) → `RuntimeException`.
3. **`PerformanceSampleStoreTest`** used `assertSame(6.0, …)` but JSON decode returns int `6`.

### Fix

- Explicit `->constructor(get(...))` for `MetricsController`, `PerformanceGuardMiddleware`, `PerformanceIncidentService`, `SafeRemediationService`, `PerformanceAggregator`, and `PerformanceBreachStore` in `Core/Performance/Config/services.php`.
- Extend `SettingsSchema` `permissionsAdmin` default with `git:publish,metrics:read`.
- Wrap route-context lookup in try/catch; fall back to `sanitizePath()`.
- Use `assertEquals` for JSON-numeric duration in ring-buffer test.

### Evidence and traceability

- **Log:** `alltests_050826_1643.log` — PHPUnit `Errors: 233`, `bin/console` fatal on `MetricsController`.
- **Key files:** `backend/app/Core/Performance/Config/services.php`, `backend/app/Http/Routes/metrics.php`, `SettingsSchema.php` (`permissionsAdmin`), `PerformanceRouteLabelResolver.php`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · [ITERATION_71](en/ITERATION_71.md)

### Verification

```bash
./scripts/iteration-gate.sh
APP_ENV=testing php -r "require 'vendor/autoload.php'; require 'backend/bootstrap/app.php'; echo 'OK';"
./vendor/bin/phpunit backend/tests/Http/Controllers/Admin/MetricsControllerTest.php
```

### Ops note (existing installs)

Instances that already saved **`accessControl.permissionsAdmin`** without `metrics:read` must add it in **Settings → Access control** (SUPER_ADMIN) or rely on SUPER_ADMIN role for APM API access until updated.

<a id="iss-128"></a>

## ISS-128 – Engine Performance Guard settings could not be saved (`float` field / Zod)

[↑ Overview](#overview)

**Severity:** Medium (admin UX)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** Reported on production after It.71 deploy — enabling **Performance Guard** in **Settings → Engine** and clicking **Save** had no effect (client-side validation blocked submit).

### Operational synopsis

It.71 added `performanceGuardSampleRate` to the **engine** settings group with schema type **`float`** and rules `numeric, min:0, max:1`.

Two gaps prevented a successful save from the admin UI:

1. **Frontend field rendering** — `SettingFieldRow` supported `bool`, `int`, `string`, etc., but not **`float`**. The sample-rate field was never registered in react-hook-form, while other Performance Guard fields were.
2. **Client Zod mirror** — `zodFromRules()` only branched on `bool` and `int`. Rules without those tokens fell through to **`applyStringRules`**, so `min:0` / `max:1` were interpreted as **string length**, not numeric bounds. Loaded default `1.0` (number) failed validation silently before the PUT request.

Backend `Validator.php` recognizes **`number`**, not **`numeric`** — schema used `numeric`, so server-side min/max for sample rate were also skipped when the field was sent.

### Fix

- Add **`float`** to `SettingFieldType` and render a number input (`step="any"`, `valueAsNumber`) in `SettingsView.tsx`.
- Add **`applyNumberRules`** in `zodFromRules.ts` for `number` / `numeric` rules.
- Change `SettingsSchema` rule **`numeric` → `number`** for `performanceGuardSampleRate`.
- Regression: `SettingsControllerEngineTest::testEngineSettingsAcceptPerformanceGuardEnabledWithSampleRate`, Vitest for float sample rate.

### Evidence and traceability

- **Symptom:** User enables `performanceGuardEnabled` → Save appears to do nothing (Zod error on hidden/unrendered float field).
- **Key files:** `SettingsSchema.php` (engine group), `frontend/src/validation/zodFromRules.ts`, `frontend/src/components/backend/SettingsView.tsx`, `frontend/src/api/settings.ts`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · [ITERATION_71](en/ITERATION_71.md)

### Verification

```bash
./scripts/iteration-gate.sh
./vendor/bin/phpunit backend/tests/Http/Controllers/Admin/SettingsControllerEngineTest.php
cd frontend && npm test -- zodFromRules.test.ts
```

### Ops note

After deploy, **Settings → Engine → Enable Performance Guard** → **Save** should persist. Tune sample rate and latency budgets as needed; ensure **`metrics:read`** is on ADMIN ACL if the dashboard panel should be visible ([ISS-127](#iss-127)).

<a id="iss-129"></a>

## ISS-129 – FileDriver emitted PHP warning on read-only cache dir (CI PHPUnit)

[↑ Overview](#overview)

**Severity:** Low (CI / cache)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** Local **`alltests`** / CI PHPUnit after It.71 — suite reported **0 failures** but **1 PHP warning**, failing the job.

### Operational synopsis

`SafeRemediationServiceTest::testAutomaticSkipsWhenCacheCapabilityFails()` creates a cache directory, sets it **read-only** (`chmod 0555`), and expects automatic Performance Guard remediation to detect cache failure and return `cache_capability_failed` without applying purge.

`FileDriver::set()` called `file_put_contents()` unconditionally on the health-probe path. On a read-only directory PHP emitted:

`file_put_contents(...): Failed to open stream: Permission denied`

PHPUnit treats warnings as CI failure even when assertions pass. The test expectation (`applied === false`, `detail === cache_capability_failed`) was correct; the driver should fail **silently** with `false`, not raise a warning.

### Fix

- **`FileDriver::writeFile()`** — check `is_dir()` + `is_writable()` on the target directory before write (skip writability pre-check on `vfs://` — vfsStream quirk in `CacheManagerTest`); use `@file_put_contents(..., LOCK_EX)` on real paths and omit `LOCK_EX` on vfs; return `false` on failure.
- **`set()`** and **`saveTagIndex()`** route through `writeFile()` so tag-index writes follow the same guard.

Higher-level services (`CacheCapabilityProbe`, `SafeRemediationService`) already interpret `health()['ok'] === false` as “cache unavailable”.

### Evidence and traceability

- **Symptom:** PHPUnit summary `Warnings: 1` at `FileDriver.php:47`; triggered by `SafeRemediationServiceTest::testAutomaticSkipsWhenCacheCapabilityFails`.
- **Key files:** `backend/app/Core/Cache/Drivers/FileDriver.php`, `backend/tests/Core/Performance/SafeRemediationServiceTest.php`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28)

### Verification

```bash
./vendor/bin/phpunit backend/tests/Core/Performance/SafeRemediationServiceTest.php --display-warnings
./vendor/bin/phpunit --display-warnings
./scripts/iteration-gate.sh
```

Expected: **0 warnings**, remediation test still passes.

<a id="iss-130"></a>

## ISS-130 – Newsletter unreadable in light mode (admin + public modal)

[↑ Overview](#overview)

**Severity:** Medium (UX)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** Post–It.71 UX polish (Phase A) — reported on production and in local tests.

### Operational synopsis

Two separate surfaces shared the same root cause pattern: **low-contrast tokens on light backgrounds**.

1. **Admin → Newsletter** — `NewsletterSubscribersPanel` and `NewsletterSettingsPanel` used `theme-*` CSS variables (`text-theme-text-muted`, `bg-theme-surface-elevated/60`) while the admin shell uses `bg-slate-50`. Cards and table headers blended into the page background.

2. **Public newsletter modal** — `NewsletterPreferenceFields` used `border-white/10 bg-black/10` and `opacity-70` hint text, styled for dark footer overlays. Inside `NewsletterSubscribeModal` (`bg-theme-surface-elevated`, light mode) preference labels were nearly invisible. Success state used `text-emerald-100` on light green — also unreadable.

### Fix

- Admin panels: align with Analytics admin styling (`slate-*` + `dark:`).
- Public preference cards: `text-theme-text`, `text-theme-text-muted`, `border-theme-border`, `bg-theme-surface`.
- Modal / maintenance success banners: `text-emerald-900` + `bg-emerald-50` in light; keep dark variants.

### Evidence and traceability

- **Symptom:** User screenshot — “Čo chcete dostávať?” and option labels invisible in subscribe modal.
- **Key files:** `NewsletterPreferenceFields.tsx`, `NewsletterSubscribeModal.tsx`, `NewsletterSubscribersPanel.tsx`, `NewsletterSettingsPanel.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · UX Phase A

### Verification

Manual: Settings → enable footer newsletter → open modal on public site in **light** appearance; labels and hints must be readable. Admin → Newsletter table readable in light admin theme.

<a id="iss-131"></a>

## ISS-131 – PHP_CodeSniffer blame-report command injection (CVE-2026-67434)

[↑ Overview](#overview)

**Severity:** High (dev dependency / CI)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** `composer audit` in local `alltests` / CI after 6 Aug 2026 PHPCS advisory.

### Operational synopsis

**GHSA-hmqg-cxww-wqhq** / **CVE-2026-67434**: PHP_CodeSniffer `< 3.13.6` and `>= 4.0.0, < 4.0.2` — command injection when processing specially crafted filenames with **Gitblame / Hgblame / Svnblame** report formats.

PaginiumCMS uses PHPCS only as a **dev** dependency (`composer.json` require-dev). Default project scripts do not invoke blame reports; risk is confined to CI/dev environments scanning untrusted trees with those report flags.

Lock file had `squizlabs/php_codesniffer` **4.0.1** → flagged by `composer audit`.

### Fix

- Require `squizlabs/php_codesniffer`: `^4.0.2` in `composer.json`.
- Updated lock to **4.0.4** (`composer update squizlabs/php_codesniffer`).
- `composer audit` → **0 advisories**.

### Evidence and traceability

- **Advisory:** [GHSA-hmqg-cxww-wqhq](https://github.com/PHPCSStandards/PHP_CodeSniffer/security/advisories/GHSA-hmqg-cxww-wqhq)
- **Key files:** `composer.json`, `composer.lock`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28)

### Verification

```bash
composer audit
composer show squizlabs/php_codesniffer | grep versions
```

Expected: no advisories; version `>= 4.0.2`.

### Ops note

Production runtime is **not** affected (PHPCS is not installed in prod `composer install --no-dev`). Re-run `composer install` on dev/CI after pull.

<a id="iss-132"></a>

## ISS-132 – `analyticsChartData.ts` wrong import depth (TS2307)

[↑ Overview](#overview)

**Severity:** Low (CI / frontend type-check)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** GitHub Actions frontend job failed after Phase B analytics charts (`83546d7`).

### Operational synopsis

New helper `frontend/src/components/backend/analytics/analyticsChartData.ts` imported types from `../../api/analytics`. From `components/backend/analytics/`, the path to `src/api/analytics` requires **three** parent segments (`../../../api/analytics`), not two.

CI `npm run type-check` reported:

```text
TS2307: Cannot find module '../../api/analytics'
```

### Fix

- Change import to `../../../api/analytics` (`c429070`).

### Evidence and traceability

- **Key files:** `frontend/src/components/backend/analytics/analyticsChartData.ts`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · [ITERATION_UX_POLISH](en/ITERATION_UX_POLISH.md)

### Verification

```bash
cd frontend && npm run type-check
```

<a id="iss-133"></a>

## ISS-133 – BackToTopButton Vitest missing `I18nProvider`

[↑ Overview](#overview)

**Severity:** Low (CI / Vitest)  
**Status:** ✅ **2.1.0-beta.28**  
**Release context:** After admin back-to-top in `ResponsiveLayout` (`d5140e1`), CI Vitest failed with context error.

### Operational synopsis

`BackToTopButton` calls `useI18n()` for `public.backToTop.label`. Tests that render `ResponsiveLayout` or `BackToTopButton` directly used plain `@testing-library/react` `render()` without `I18nProvider`:

```text
useI18n must be used within I18nProvider
```

Affected suites:

- `BackToTopButton.test.tsx`
- `ResponsiveLayout.test.tsx`
- `adminRouteTransitions.test.tsx`

### Fix

Use shared `renderWithProviders()` from `frontend/src/test/renderWithProviders.tsx` (wraps `TestI18nProvider` + `TestSettingsProvider`) in all affected tests (`486debe`).

### Evidence and traceability

- **Key files:** `BackToTopButton.test.tsx`, `ResponsiveLayout.test.tsx`, `adminRouteTransitions.test.tsx`, `renderWithProviders.tsx`
- **History:** [CHANGELOG](../CHANGELOG.md#release-2-1-0-beta-28) · [ITERATION_UX_POLISH](en/ITERATION_UX_POLISH.md)

### Verification

```bash
cd frontend && npm test -- --run \
  src/components/frontend/BackToTopButton.test.tsx \
  src/components/layout/ResponsiveLayout.test.tsx \
  src/test/adminRouteTransitions.test.tsx
```

<a id="iss-134"></a>

## ISS-134 – HTTP PHPUnit flaky auth and system-update tests (401/403/404)

[↑ Overview](#overview)

**Severity:** Medium (CI / test hygiene)  
**Status:** ✅ **2.1.0-beta.29** · commits `e0d45d0`, `2c83615` on `main`  
**Related:** [ISS-073](#iss-073) (login lockout persistence), [ISS-123](#iss-123) (settings test file), [ISS-125](#iss-125) (demo env pollution — distinct root cause)

### Operational synopsis

After It.72/73 landed on `main`, CI and local **full-suite** PHPUnit runs intermittently reported four HTTP controller failures that **passed in isolation**:

| Test | Expected | Got | Symptom |
|------|----------|-----|---------|
| `BackupControllerTest::testDeleteBackup` | login 200 | 401 | Admin login failed before backup API |
| `SystemUpdateControllerTest::testRunQueuesJobWhenEnabled` | run 200 | 403 | `System deploy is disabled in settings` |
| `TwoFactorControllerTest::testDisableTwoFactor` | login 200 | 401 | User not authenticated mid-flow |
| `TwoFactorControllerTest::testGetQRCode` | QR 200 | 404 | `findByEmail()` returned null after verify |

Reproduction rate for `testRunForbiddenWhenDeployDisabled` → `testRunQueuesJobWhenEnabled` in one process was **~87% failure** before the fix.

### Root cause

Two independent pollution mechanisms:

1. **`settings.testing.json` read/write race** — `SettingsRepository::setGroup()` writes under `flock(LOCK_EX)`, but `group()` read the file via unlocked `storage->read()`. When `systemUpdate.deployEnabled` was set **before** register/login, auth middleware read settings during HTTP requests and sometimes observed an empty or stale override set → effective `deployEnabled: false` (schema default).

2. **Shared login-attempt key `ip:unknown`** — `Http/TestCase::createJsonRequest()` did not set `REMOTE_ADDR`. Failed login tests across the suite accumulated on the same IP bucket; combined with incomplete disk purge of `login_attempts.json`, later tests saw **401** (wrong credentials path) or **429** lockout depending on order.

QR **404** on `getQrCode` was a downstream symptom: session user email did not resolve in `UserRepository` when auth/login failed or storage context was inconsistent after polluted state.

### Fix

- **`backend/tests/Http/TestCase.php`**
  - `TestStorageCleaner::purgeLoginAttempts()` in `setUp` (before `LoginAttemptTracker::clearAll()`).
  - Per-request synthetic `REMOTE_ADDR` (`10.255.x.x`) on all JSON requests.
- **`SystemUpdateControllerTest`**
  - Apply `systemUpdate` overrides **after** `loginAsSuperAdminUser()`, not before auth HTTP traffic.
  - Same ordering for deploy-disabled and empty-ref cases.
- Richer assertion message on run endpoint (JSON body) for faster CI diagnosis.

**Not adopted:** `SettingsRepository::reset()` in every HTTP `setUp` — caused nested `flock` deadlock via `DocumentValidator` → `get()` during locked writes.

### Evidence and traceability

- **Commits:** `e969a86` (It.72/73), `e0d45d0` (test isolation)
- **Key files:** `backend/tests/Http/TestCase.php`, `backend/tests/Http/Controllers/Admin/SystemUpdateControllerTest.php`, `data/settings.testing.json`, `data/security/login_attempts.json`
- **History:** [CHANGELOG](../CHANGELOG.md#unreleased)

### Verification

```bash
# Pair that previously flaked ~87%:
for i in $(seq 1 20); do
  ./vendor/bin/phpunit --filter 'testRunForbiddenWhenDeployDisabled|testRunQueuesJobWhenEnabled' \
    backend/tests/Http/Controllers/Admin/SystemUpdateControllerTest.php || exit 1
done

# Previously failing controllers:
./vendor/bin/phpunit \
  backend/tests/Http/Controllers/Admin/BackupControllerTest.php \
  backend/tests/Http/Controllers/Admin/SystemUpdateControllerTest.php \
  backend/tests/Http/Controllers/Auth/TwoFactorControllerTest.php

./scripts/iteration-gate.sh
```

---

<a id="iss-135"></a>

## ISS-135 – Shortcode expand template uses regex denylist instead of full HTML allowlist

[↑ Overview](#overview)

**Severity:** Low (defense-in-depth)  
**Status:** ⏳ Deferred · It.67+ hardening slice

### Operational synopsis

Security audit flagged `ShortcodeDefinitionPolicy` expand-body validation as **denylist-first** (`FORBIDDEN_EXPAND_PATTERNS`: script, iframe, event handlers, PHP tags). CSS classes in `class=` attributes already use a **partial allowlist** (`pg-*`, `paginium-public-*`, `prose*`).

**Risk today:** Low — definitions are admin-authored JSON, validated by `CodePolicyEngine`, never executed as PHP; expand output is sanitized at render time like other untrusted HTML.

**Planned hardening:** Replace open-ended HTML in expand templates with a strict tag/attribute allowlist (mirroring public HTML sanitizer policy), or require expand bodies to reference only registered layout partials.

### Evidence and traceability

- **Key file:** `backend/app/Core/CodePolicy/Services/ShortcodeDefinitionPolicy.php`
- **Related:** [ISS-086](#iss-086) (stored XSS), It.67 untrusted surfaces
- **Tests:** `backend/tests/Core/CodePolicy/CodePolicyEngineTest.php` (shortcode policy cases)

### Verification

```bash
./vendor/bin/phpunit backend/tests/Core/CodePolicy/CodePolicyEngineTest.php \
  --filter ShortcodeDefinitionPolicy
```

---

<a id="iss-136"></a>

## ISS-136 – Blog “About author” showed article excerpt/SEO text instead of author bio

[↑ Overview](#overview)

**Severity:** Medium (UX / content)  
**Status:** ✅ Fixed — **`v2.1.0-beta.38`**

### Operational synopsis

Public article detail rendered the “About the author” block using `article.excerpt` or `frontMatter.description` (perex/SEO fields), so visitors saw truncated article body or meta description under the author heading.

### Resolution

- **`BlogAuthorSettings`** — site-wide author name, bio, avatar URL, and show/hide toggle under **Settings → Content**.
- **`ContentController`** — exposes `authorBio`, `authorAvatarUrl`, `showAuthorBox` on article payloads from settings (optional per-article `authorBio` override in front matter).
- **`BlogRenderer`** — uses API fields only; hides the box when bio is empty.

### Evidence and traceability

- **Key files:** `frontend/src/components/frontend/BlogRenderer.tsx`, `backend/app/Core/Content/BlogAuthorSettings.php`, `backend/app/Core/Settings/SettingsSchema.php`
- **Tests:** `backend/tests/Core/Content/BlogAuthorSettingsTest.php`

---

<a id="iss-137"></a>

## ISS-137 – Admin user avatar upload failed (multipart without boundary)

[↑ Overview](#overview)

**Severity:** Medium (admin UX)  
**Status:** ✅ Fixed — **`v2.1.0-beta.38`**

### Operational synopsis

`uploadUserAvatar()` set `Content-Type: multipart/form-data` manually without a `boundary`, so PHP did not parse the upload. Admin saw HTTP 400 *“Súbor avataru je povinný”*.

### Resolution

- Remove manual `Content-Type` on FormData POSTs; axios/browser sets `multipart/form-data; boundary=…`.
- **`api/client.ts`** — request interceptor deletes default JSON `Content-Type` when body is `FormData`.
- Same fix applied to **`frontend/src/api/media.ts`** upload helper.

### Evidence and traceability

- **Key files:** `frontend/src/api/users.ts`, `frontend/src/api/client.ts`, `backend/app/Http/Controllers/Admin/UserController.php` (stream read hardening)

---

<a id="iss-138"></a>

## ISS-138 – Blog author identity had no settings or editor path

[↑ Overview](#overview)

**Severity:** Medium (UX)  
**Status:** ✅ Fixed — **`v2.1.0-beta.38`**

### Operational synopsis

Articles displayed author label from i18n default “Redakcia” / logged-in editor name in preview only. There was no admin settings field for public author identity and no **Author** field in the article editor. Operators assumed a CMS user account named “Redakcia” was required.

### Resolution

- Settings keys: `blogAuthorName`, `blogAuthorBio`, `blogAuthorAvatarUrl`, `blogShowAuthorBox`.
- Article editor sidebar: optional **Author** override (empty = site default).
- Author is **not** tied to CMS user accounts.

---

<a id="iss-139"></a>

## ISS-139 – GDPR re-export after anonymize re-aggregated pseudonym-linked rows

[↑ Overview](#overview)

**Severity:** Medium (GDPR / data handling)  
**Status:** ✅ Fixed — **`v2.1.0-beta.38`**

### Operational synopsis

After `GdprAnonymizeService` redacted comments/messages to the subject’s pseudonym e-mail and display name, `GdprExportService::buildExport()` matched those same pseudonym values on the anonymized user profile and returned non-empty `comments` / `contactMessages` arrays (PHPUnit regression in `GdprAnonymizeServiceTest`).

### Resolution

When `GdprPseudonym::isAnonymizedEmail()` is true for the subject account, export returns profile JSON only; related flat-file rows are omitted (already redacted in primary stores).

### Evidence and traceability

- **Key files:** `backend/app/Core/Gdpr/Services/GdprExportService.php`, `backend/app/Core/Gdpr/Services/GdprAnonymizeService.php` (also clears `bio`)
- **Tests:** `backend/tests/Core/Gdpr/Services/GdprAnonymizeServiceTest.php`

---

<a id="iss-140"></a>

## ISS-140 – API4 gaps: contact/comments abuse and unbounded bulk/import/export (It.80f)

[↑ Overview](#overview)

**Severity:** Medium (security / resource consumption)  
**Status:** ✅ Fixed — **`v2.1.0-beta.38`** · It.80f

### Operational synopsis

OWASP API4-style gaps remained after global rate limiting: `POST /api/contact` had no honeypot or dedicated limit; `POST /api/comments` relied only on spam heuristics; admin bulk mutations accepted unbounded `ids` arrays; backup ZIP import had no size cap; GDPR export could aggregate unbounded comment/message volumes.

### Resolution (It.80f)

| Control | Detail |
|---------|--------|
| Contact | `ContactRateLimitMiddleware` (5/h IP, 3/day e-mail) + honeypot `_hp` |
| Comments | `CommentSubmitRateLimitMiddleware` (15/h IP) |
| Bulk | `BulkOperationLimits::MAX_IDS = 100` |
| Backup import | `uploadSecurity.backupImportMaxSizeKb` (default 100 MB) |
| GDPR export | caps 5000 comments / 2000 contact messages |
| CLI | `php backend/bin/console redirect:validate` |

### Evidence and traceability

- **Docs:** [ITERATION_80](en/ITERATION_80.md) checklist 80f
- **Tests:** `backend/tests/Http/Support/BulkOperationLimitsTest.php`

---

<a id="iss-141"></a>

## ISS-141 – System Update deploy 422 after BodyParsingMiddleware (beta.39 regression)

[↑ Overview](#overview)

**Severity:** High (deploy)  
**Status:** ✅ Fixed — **`v2.1.0-beta.40`**

### Operational synopsis

`v2.1.0-beta.39` added Slim `BodyParsingMiddleware` in `backend/bootstrap/app.php`. Controllers that read JSON only via `$request->getBody()` received an empty string on production PHP-FPM (`php://input` is not seekable after the middleware consumes it). `POST /api/admin/system/update/run` then saw a missing `ref` and returned HTTP 422 (“Validačná chyba” in admin logs). Admin UI could not deploy from beta.38 → beta.39+.

### Resolution

- `RequestJsonBody::decode()` — prefers `getParsedBody()`, falls back to raw stream.
- `SystemUpdateController::run()`, `UserController::parseJsonBody()`, and **`CommentsController`** (submit/update/bulk JSON paths) migrated to the helper.
- Regression tests: `RequestJsonBodyTest`, `SystemUpdateControllerTest::testRunUsesParsedBodyWhenStreamIsEmpty`, **`CommentsControllerTest::testApproveCommentUsesParsedBodyWhenStreamIsEmpty`**.
- **Test harness:** `Http\TestCase::rebootstrapApplication()` restores `APP_ENV=testing` and re-applies `settings.testing.json` workflow defaults after demo tests re-bootstrap the app (fixes intermittent OTP workflow PHPUnit failures).
- **Follow-up (post-beta.40):** all remaining `Http/Controllers/*` and OTP/contact rate-limit middleware migrated to `RequestJsonBody` (eliminates ISS-141 class site-wide).

### Workaround (before beta.40 on server)

```bash
APP_ROOT=/var/www/paginiumcms.com GIT_REF=v2.1.0-beta.40 ./scripts/deploy-instance-update.sh
```

### Evidence and traceability

- **Release:** [CHANGELOG.md](../CHANGELOG.md#release-2-1-0-beta-40)
- **Tests:** `backend/tests/Http/Support/RequestJsonBodyTest.php`, `backend/tests/Http/Controllers/Admin/SystemUpdateControllerTest.php`, `backend/tests/Http/Controllers/Comments/CommentsControllerTest.php`, `backend/tests/Core/Workflow/OtpWorkflowServiceTest.php`

---

