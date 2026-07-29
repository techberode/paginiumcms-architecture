# PaginiumCMS — Feature overview (implemented vs planned)

> **Purpose:** Single map of what the project **already ships**, what is **backlog / pre-Final**, and what **checklists** still need attention.  
> **Current release:** **`v2.1.0-beta.18`** · **2026-07-29**  
> **Architecture:** React SPA (Vite 8, TS) ↔ Slim 4 REST API ↔ PHP 8.5 flat-file core (no SQL)

| Symbol | Meaning |
|--------|---------|
| ✅ | Shipped and wired end-to-end (or ops-complete) |
| 🟡 | Partial — backend or UI exists, polish / UX / docs gap |
| ⏳ | Planned — documented in iteration spec |
| 🔒 | SUPER_ADMIN or ADMIN + 2FA (typical) |
| 🛠 | Developer Mode unlock required |

**Related docs:** [CHECKLIST.md](CHECKLIST.md) (API/FE matrix, older baseline) · [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) · [ROADMAP.md](ROADMAP.md) · [PUBLIC_BETA1.md](PUBLIC_BETA1.md) · [ISSUES.md](ISSUES.md)

---

## 1. Release snapshot

| Milestone | Tag / version | Scope |
|-----------|---------------|--------|
| Core CMS (2.0.x) | `v2.0.58` … `2.0.58` | Auth, content, media, WAF, i18n waves, plugins |
| Public Beta 1 | `v2.1.0-beta.1`–`.3` | Beta docs, security patches, tester path |
| Post-beta features | `beta.4`–`.18` | Nav, analytics, demo, newsletter, system update |
| **Latest** | **`v2.1.0-beta.18`** | It.61 Phase 5 footer + It.63 v2/v3 (compare, deploy, webhook) |
| Final 1.0 GA | not tagged | After beta testing + **It.25** setup wizard & one-click update UX |

**Production instances:** `paginiumcms.com` (prod) · `demo.paginiumcms.com` (demo, `DEMO_MODE=true`)

---

## 2. Implemented — by domain

### 2.1 Authentication & users ✅

| Feature | Backend | Admin UI | Notes |
|---------|---------|----------|-------|
| Session login / logout | ✅ | ✅ | CSRF, session regeneration |
| Register + email OTP | ✅ | ✅ | Toggle `general.allowRegistration` |
| Password reset / change | ✅ | ✅ | Generic auth responses |
| 2FA TOTP + QR | ✅ | ✅ | `/account/security` |
| Password confirm (register, admin users) | ✅ | ✅ | 2.0.56 |
| RBAC roles + permissions | ✅ | ✅ | `PermissionMiddleware` on writes |
| User admin (create, disable, 2FA) | ✅ | ✅ | `/users` 🔒 |
| Path ACL (content/media scopes) | ✅ | ✅ | Settings → access control |

### 2.2 Content & editor ✅

| Feature | Backend | Admin UI | Notes |
|---------|---------|----------|-------|
| Pages & articles CRUD | ✅ | ✅ | Flat-file index + JSON/MD |
| Markdown + WYSIWYG (Tiptap JSON) | ✅ | ✅ | It.54–55 modular profiles |
| SEO panel + suggest-meta | ✅ | ✅ | It.27, It.57 auto tags/description |
| Drafts auto-save | ✅ | ✅ | 60 s interval |
| Content locking + heartbeat | ✅ | ✅ | It.1, 409 on conflict |
| Version history + diff | ✅ | ✅ | In editor |
| 3-way merge / conflicts UI | ✅ | ✅ | It.2 |
| Scheduled publish | ✅ | ✅ | It.59 — **requires cron** |
| OTP publish workflow | ✅ | ✅ | It.41 — optional per settings |
| Bulk actions (content, trash, comments) | ✅ | ✅ | It.28 |
| Admin list modes (list / preview / grid) | ✅ | ✅ | It.27 |
| Filters, blog pagination, prev/next | ✅ | ✅ | It.44 + public blog |
| Live preview modal | ✅ | ✅ | It.51 `SitePreviewModal` |
| Custom editor components (plugins) | ✅ | ✅ | It.60 hello-widget demo |
| Reading time, date badges | ✅ | ✅ | It.51 |

### 2.3 Media (DAM) ✅

| Feature | Backend | Admin UI | Notes |
|---------|---------|----------|-------|
| Upload, folders, bulk delete | ✅ | ✅ | `/media` |
| Stock image library + import | ✅ | ✅ | It.24, topic in settings |
| Lightbox preview | ✅ | ✅ | It.26 |
| Public `/storage/` serving | ✅ | ✅ | Allow-list, attachment for SVG/HTML |
| Editor image upload | ✅ | ✅ | It.55 |

### 2.4 Public website ✅

| Feature | Status | Notes |
|---------|--------|-------|
| React public routes (home, pages, blog) | ✅ | Same SPA as admin |
| Public i18n SK/EN | ✅ | 2.0.50 wave |
| Rich navigation (icons, descriptions) | ✅ | It.56, beta.5 |
| Color schemes + light/dark/system | ✅ | **It.58b** shipped |
| RSS, sitemap, robots.txt | ✅ | It.10 |
| Maintenance / coming soon mode | ✅ | Hero image, newsletter capture |
| Cookie consent banner | ✅ | beta.17, GDPR settings |
| Contact form | ✅ | It.52 |
| Footer newsletter (inline email → modal) | ✅ | It.61 Phase 5, beta.18 |
| Newsletter confirm / manage / unsubscribe | ✅ | It.61 Phases 3–4 public pages |

### 2.5 Newsletter & campaigns ✅

| Feature | Backend | Admin UI | Notes |
|---------|---------|----------|-------|
| Footer + maintenance subscribe | ✅ | ✅ | Unified `NewsletterRepository` |
| Preferences (weekly digest, new article, …) | ✅ | ✅ | |
| Double opt-in | ✅ | ✅ | `/newsletter/confirm` |
| Admin subscriber list + CSV export | ✅ | ✅ | `/newsletter` |
| Weekly digest + new-article mail cron | ✅ | ✅ | `NewsletterMailService` |
| CMS release campaigns (SUPER_ADMIN) | ✅ | ✅ | beta.17 |
| Optional footer display modes A/C | ⏳ | — | Backlog in It.61 |

### 2.6 Admin platform & ops ✅

| Feature | Backend | Admin UI | Notes |
|---------|---------|----------|-------|
| Dashboard KPI + activity | ✅ | ✅ | It.52, ISS-047 fixed |
| Analytics (geo, referrer, device) | ✅ | ✅ | It.33 + SPA pageview beacon (nginx static fix) |
| Settings schema-driven forms | ✅ | ✅ | Encrypted password fields |
| Admin i18n SK/EN + translation editor | ✅ | ✅ | It.18 |
| Command palette search `Ctrl+K` | ✅ | ✅ | It.43 |
| Sidebar item counts | ✅ | ✅ | It.42 `/api/admin/counts` |
| Job scheduler + queue | ✅ | ✅ | It.29 `/scheduler` |
| Scheduler prod hardening | ✅ | ✅ | It.62 outcome UX, `jobs:run` |
| Backups create / restore / import ZIP | ✅ | ✅ | Zip-Slip hardened beta.6 |
| Trash soft-delete | ✅ | ✅ | |
| Audit trail + CSV export | ✅ | ✅ | Sanitized beta.2 |
| Security audit log | ✅ | ✅ | `/security/audit` |
| WAF firewall admin | ✅ | ✅ | It.50 `/firewall` |
| HTTP + app logs viewer | ✅ | ✅ | Bulk, pagination 2.0.51 |
| Notifications (SMTP, ntfy, Telegram, Discord, webhook) | ✅ | ✅ | It.6, It.47 ntfy auth |
| Monitoring reports (email HTML) | ✅ | ✅ | It.7 cron |
| Health panel | ✅ | ✅ | `/api/health` |
| GitHub **content** sync | ✅ | ✅ | `/github` — not code deploy |
| **System update** (code deploy) | ✅ | ✅ 🔒 | It.63 — `/platform/update` |
| Deploy latest tag + compare commits | ✅ | ✅ | It.63 v2 |
| GitHub release webhook auto-deploy | ✅ | 🟡 | It.63 v3 — ops; needs settings |
| Demo manager + reset seed | ✅ | ✅ | It.13 v3/v4 — demo instance only |
| Blueprints manager | ✅ | ✅ | It.12 |
| External plugins (ZIP import, hooks) | ✅ | ✅ | It.15 `/extensions` |
| Code editor + policy engine | ✅ | ✅ 🛠 | It.14–16 |
| Developer unlock + debug logs | ✅ | ✅ 🛠 | |
| SSO OAuth routes | ✅ | 🟡 | It.11 — settings-dependent |
| Branding + access control UI | ✅ | ✅ | 2.0.52 |

### 2.7 Security baseline ✅ (ongoing discipline)

| Area | Status |
|------|--------|
| CSRF on mutating API | ✅ `CsrfMiddleware` |
| Encryption at-rest (`APP_KEY`) | ✅ users + settings secrets |
| SSRF guard on outbound URLs | ✅ `OutboundUrlGuard` |
| Log/CSV injection sanitization | ✅ `LogSanitizer` |
| WAF + body scan (editor exempt) | ✅ |
| Rate limits (login, OTP, newsletter) | ✅ |
| Plugin code policy + Zip-Slip | ✅ |
| Demo mode fail-closed on prod | ✅ ISS-106 |

### 2.8 DevOps & documentation ✅

| Item | Status |
|------|--------|
| Docker Compose + `first-run.sh` | ✅ |
| `iteration-gate.sh` (PHPStan L8, PHPUnit, tsc, ESLint, Vitest) | ✅ |
| Deploy guides (prod, demo, release tag) | ✅ [deploy/DEPLOY.md](deploy/DEPLOY.md) |
| Cron documentation | ✅ [deploy/CRON.md](deploy/CRON.md) |
| User guides (install, admin, beta tester) | ✅ `docs/user/*` |
| GitHub Releases beta tags | ✅ through **beta.18** |

---

## 3. Backend API surface (42 route modules)

Auto-discovered from `backend/app/Http/Routes/` (+ auth inline in `bootstrap/app.php`):

`analytics` · `audittrail` · `blueprints` · `cache` · `codeeditor` · `comments` · `conflicts` · `contact` · `content` · `counts` · `dashboard` · `debug` · `demo` · `developer` · `drafts` · `extensions` · `feeds` · `firewall` · `github` · `health` · `jobs` · `locking` · `logs` · `maintenance` · `media` · `messages` · `navigation` · `newsletter` · `notifications` · `security` · `seo` · `settings` · `sso` · `storage` · `systemupdate` · `translations` · `trash` · `users` · `validation` · `versions` · `webhooks` · `workflows`

Detail: [architecture/API.md](architecture/API.md) · [CHECKLIST.md §1](CHECKLIST.md#1-backend-api-inventory-30-route-files)

---

## 4. Admin SPA routes (implemented)

| Route | Module |
|-------|--------|
| `/dashboard`, `/analytics` | Dashboard, analytics |
| `/pages`, `/articles`, `/media`, `/navigation` | Content |
| `/comments`, `/messages`, `/newsletter` | Engagement |
| `/github`, `/platform/update` | Integrations / deploy |
| `/scheduler`, `/backups`, `/trash` | Ops |
| `/firewall`, `/logs`, `/audit`, `/security/audit` | Security |
| `/notifications`, `/settings`, `/translations` | Platform |
| `/users`, `/account/security` | Users |
| `/blueprints`, `/extensions`, `/demo` | Extensions & demo |
| `/code-editor`, `/developer/logs` | Developer 🛠 |

Public (same SPA): `/`, `/blog`, `/blog/:slug`, `/:slug`, `/newsletter/*`

---

## 5. Planned — backlog & pre-Final

### 5.1 Pre-Final gate (high intent)

| It. | Name | Priority | Notes |
|-----|------|----------|-------|
| **25** | [Setup wizard + one-click updates](ITERATION_25.md) | 🟡 **Last major step before 1.0** | Onboarding; dashboard “Update now”; It.63 engine already ✅ |
| — | **Community beta testing** | 🔴 | [BETA_TESTER.md](user/BETA_TESTER.md) — not fully executed yet |
| — | **Final 1.0 GA tag** | ⏳ | After testing + It.25 |

### 5.2 Feature backlog (post-beta, not blocking beta.18)

| It. | Name | Priority | Status |
|-----|------|----------|--------|
| **65** | [Feature gallery — admin screenshots](ITERATION_65.md) | 🟡 | ✅ Phase 1 grid + modal (`beta.20`); Phase 2 slider |
| **58a** | Page layout builder (5 templates) | 🟡 | ⏳ 58b (color schemes) ✅ |
| **48** | PHP templates + static HTML export | 🟡 | ⏳ |
| **49** | Unified cache (file + Redis) | 🟡 | ⏳ absorbs It.45 |
| **46** | Server metrics agent | 🟡 | ⏳ extends It.7 monitoring |
| **30** | Contextual actions | 🟡 | ⏳ |
| **31** | Live preview (in-editor iframe) | 🟡 | ⏳ partial via It.51 modal |
| **32** | React chunking + OPcache preload | 🔵 | ⏳ perf |
| **34** | System overview (stack versions) | 🟡 | ⏳ |
| **35** | Flat-file inspector | 🟡 | ⏳ |
| **36–40** | Pagination polish, inline FE edit, feature flags, comments roles, scoped DAM | 🟡 | ⏳ |
| **16** | Full CMS themes + code editor tree | 🔵 | ⏳ partial via plugins |
| **61** | Newsletter footer modes A/C | 🔵 | ⏳ optional |

Full table: [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)

### 5.3 Explicitly out of Public Beta 1 scope

From [PUBLIC_BETA1.md](PUBLIC_BETA1.md): layout builder (58a), setup wizard (25), full theme system (16). **Beta adds** many of these partially (58b, 63, 61) — see §2 above.

### 5.4 It.63 UX deferred to It.25

| Item | Beta (It.63) | Final (It.25) |
|------|--------------|---------------|
| Remote version check | ✅ Platform → System update | ✅ + dashboard banner |
| One-click deploy tag | ✅ SUPER_ADMIN panel | ✅ simplified “Update now” |
| Webhook auto-deploy | ✅ ops / optional | Advanced / hidden |
| Setup on first install | ❌ manual settings | ✅ wizard step |
| Package update (no git) | ❌ | ⏳ stretch |

---

## 6. Open items — issues & tech debt

### 6.1 Still open (from [ISSUES.md](ISSUES.md) summary)

| ID | Topic | Severity | Action |
|----|-------|----------|--------|
| ISS-008 | Password fields over HTTP | Info | ⏳ HTTPS on production (documented) |
| ISS-011 | ESLint warnings | Low | ⏳ ~57/65 baseline cleanup |
| ISS-014 | CORS dev wildcards if wrong `APP_ENV` | Low | ⏳ verify deploy |
| ISS-082 | symfony/yaml 8.x migration | Low | ⏳ deferred |
| ISS-083 | ESLint 10 flat config | Low | ⏳ deferred |
| ISS-089 | npm audit RR high (SPA false positive) | Low | ⏳ accepted, CI critical-only |
| ISS-096 | 502 right after PHP restart | Info | Wait 5–10 s (ops) |
| ISS-099 | Demo cron storage permissions | Ops | ℹ️ chown checklist |

**Recently closed:** ISS-097 (newsletter admin), ISS-109 (footer CTA bulk → inline, beta.18), ISS-094 (scheduler prod, It.62), ISS-104/105 (audit, beta.15).

### 6.2 CHECKLIST.md gaps (doc + code hygiene)

From [CHECKLIST.md §7](CHECKLIST.md#7-known-gaps-backlog) — still relevant unless noted:

1. 🟡 Some admin screens use raw `useApi` instead of typed `frontend/src/api/*` modules  
2. ⏳ Postman/Newman smoke in CI — optional  
3. ⏳ **CHECKLIST.md itself outdated** (shows 2.0.57) — use **this file** + CHANGELOG for beta state  

### 6.3 Ops checklist (not code — verify on server)

| Check | Doc | Status |
|-------|-----|--------|
| Host cron `scheduler:run` + `worker:process` | [CRON.md](deploy/CRON.md) | ⏳ verify on prod/demo |
| Docker deploy permissions (`bootstrap-deploy-permissions.sh`) | [DEPLOY.md](deploy/DEPLOY.md) | ✅ prod paginiumcms.com |
| System update settings (deploy + webhook) | [ITERATION_63.md](ITERATION_63.md) | ✅ webhook OK on prod |
| Beta tester smoke (~30 min) | [BETA_TESTER.md](user/BETA_TESTER.md) | ⏳ community testing |
| Security review checklist | [SECURITY_REVIEW.md](SECURITY_REVIEW.md) | ⏳ for auditors |

---

## 7. Test & quality baseline

| Gate | Target | Command |
|------|--------|---------|
| PHP syntax | changed files | `php -l` (in gate) |
| PHPStan | level 8, 0 errors | `./vendor/bin/phpstan analyse backend --level=8` |
| PHPUnit | green | `./vendor/bin/phpunit` |
| TypeScript | strict | `cd frontend && npm run type-check` |
| ESLint | ≤ 65 warnings | `cd frontend && npm run lint` |
| Vitest | green | `cd frontend && npm test` |
| Full gate | all above | `./scripts/iteration-gate.sh` |

**Note:** CHECKLIST cites ~838 PHPUnit / 228+ Vitest — counts grow each iteration; run locally for current numbers.

---

## 8. Path to Final 1.0 (recommended order)

```text
1. Beta testing wave     → BETA_TESTER checklist, GitHub Issues feedback
2. Stabilize backlog     → It.58a layout builder (if prioritized), bugfixes
3. It.25                 → Setup wizard + dashboard one-click update UX
4. Docs + SECURITY_REVIEW→ ADMIN_GUIDE “Updating CMS”, INSTALLATION refresh
5. Tag 1.0.0 GA          → RELEASE.md, PUBLIC docs, remove “beta” caveats
```

---

## 9. Quick reference — iteration index (shipped highlights)

| Range | Themes |
|-------|--------|
| It.1–5 | Locking, drafts, versioning, conflicts, settings |
| It.6–7 | Notifications, monitoring reports |
| It.8–10 | Media, SEO, feeds |
| It.11–16 | SSO, demo, blueprints, code editor, plugins |
| It.18–22 | i18n, flat-file index, ops, API contract |
| It.24–30 | DAM, view modes, bulk, cron, content editors |
| It.41–44 | OTP workflows, counts, search, filters |
| It.50–57 | WAF, preview/tags, navigation, auto-meta |
| It.58b, 59–63 | Color schemes, scheduled publish, editor widgets, newsletter, scheduler hardening, **system update** |
| It.13, 61 | Demo sandbox, newsletter v2 |

Per-iteration specs: `docs/ITERATION_{N}.md` · Index: [docs/README.md](README.md)

---

*Maintainers: update this file when shipping a beta tag or closing a major iteration. Sync CHECKLIST.md header periodically or treat this doc as the living overview.*
