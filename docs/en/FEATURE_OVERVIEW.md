# PaginiumCMS — feature overview

> **Purpose:** one living inventory of what is shipped, partial, and planned  
> **Snapshot:** `v2.1.0-beta.23` · August 2, 2026  
> **Architecture:** React/Vite SPA ↔ Slim REST API ↔ PHP Core ↔ No-SQL file SSOT

**Related:** [ROADMAP.md](ROADMAP.md) · [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) · [PUBLIC_BETA1.md](PUBLIC_BETA1.md) · [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md)

| Symbol | Meaning |
|--------|---------|
| ✅ | the feature is shipped and wired |
| 🟡 | a usable foundation exists, with a concrete remainder |
| ⏳ | planned |
| 🔒 | typically ADMIN/SUPER_ADMIN, subject to ACL and 2FA policy |
| 🛠 | requires Developer Mode / unlock |

---

## 1. Release snapshot

| Milestone | Versions | Result |
|-----------|----------|--------|
| Core stabilization | `2.0.x` through `2.0.58` | content, auth, media, security, plugins, i18n, beta infrastructure |
| Public Beta 1 | `v2.1.0-beta.1` | first public beta gate and tester path |
| Beta patch series | `beta.2` through `beta.23` | hardening, UX, demo, newsletter, update, gallery, layout |
| Latest release in this snapshot | **`v2.1.0-beta.23`** | It.58c Layout Switch |
| Hybrid Engine | It.68 foundation ✅ `[Unreleased]` | storage abstraction, schema registry, engine settings; It.69+ planned |
| Final 1.0 | no tag | scope must be confirmed by a separate release gate |

---

## 2. Authentication, users, and access ✅

| Feature | Backend | UI | Note |
|---------|---------|----|------|
| Session login/logout | ✅ | ✅ | session regeneration; secure cookies per deployment |
| Registration | ✅ | ✅ | configurable through settings |
| Password reset/change | ✅ | ✅ | generic responses against account enumeration |
| TOTP 2FA | ✅ | ✅ | setup, QR, login step |
| Password confirmation | ✅ | ✅ | registration and admin user flow |
| Roles and permissions | ✅ | ✅ | RBAC + `PermissionMiddleware` |
| Path/resource ACL | ✅ | ✅ | content and media scopes |
| Email OTP workflow | ✅ | ✅ | optional approval flows |
| SSO OAuth foundation | ✅ | 🟡 | provider configuration and final UX dependent |
| API keys/JWT | ⏳ | ⏳ | It.74; additive, admin session remains |

---

## 3. Content and collaboration ✅

| Feature | Status | Note |
|---------|--------|------|
| Pages and articles CRUD | ✅ | file repositories + index |
| Markdown editor | ✅ | typed admin flow |
| WYSIWYG/Tiptap profiles | ✅ | modular toolbar, JSON storage |
| Draft auto-save | ✅ | interval and dirty-state workflow |
| Editing locks | ✅ | heartbeat + TTL |
| Optimistic concurrency | ✅ | revision + HTTP 409 |
| Three-way merge | ✅ | automatic and manual conflict resolution |
| Version history and diff | ✅ | editor integration |
| Scheduled publishing | ✅ | requires a working scheduler/cron |
| OTP publish approval | ✅ | optional through workflow settings |
| Bulk actions | ✅ | content/trash/comments/users/backups by module |
| Filters, sorting, pagination | ✅ | admin and public blog |
| SEO panel and suggest-meta | ✅ | title, description, tags, social meta |
| Live preview | 🟡 | modal/preview route exists; full in-editor mode may continue in It.58d |
| Multi-locale single document | ⏳ | It.73 |

---

## 4. Media and DAM ✅

| Feature | Status | Note |
|---------|--------|------|
| Upload and folders | ✅ | safe types and paths |
| Metadata and alt text | ✅ | editable in admin UI |
| Bulk operations | ✅ | shared selection pattern |
| Stock image import | ✅ | configurable source |
| Lightbox preview | ✅ | strict binary handling |
| Editor image upload | ✅ | Tiptap integration |
| Public `/storage/` | ✅ | allow-list and safe content headers |
| Flysystem/S3/CDN drivers | ⏳ | It.72; local driver remains default |
| Scoped Section FileManager | ⏳ candidate | remaining backlog without a reused iteration number |

---

## 5. Public website ✅

- React public routes for home, pages, blog, and article detail.
- SK/EN user interface.
- RSS, sitemap, robots, and SEO metadata.
- Rich nested navigation with descriptions and icons.
- Color schemes and light/dark/system modes.
- Maintenance/coming-soon mode.
- Cookie consent settings.
- Contact form with configurable subjects.
- Newsletter subscribe/confirm/preferences/unsubscribe and admin subscribers.
- Feature gallery with layouts, effects, deep links, and metadata export/import.
- Layout template selection shipped in It.58c.

**Remaining:** additional layout/editor polish in It.58d and optional static/Jamstack rendering in It.48/70.

---

## 6. Administration and operations ✅

| Area | Status | Main elements |
|------|--------|---------------|
| Dashboard and analytics | ✅ | KPI, activity, referrer/device/geo enrichment, SPA beacon |
| Admin search and navigation | ✅ | `Ctrl+K`, deep links, sidebar counts |
| Settings engine | ✅ | schema-driven forms, encrypted secrets |
| Admin i18n | ✅ | SK/EN + translation editor |
| Scheduler and queue | ✅ | registry, CLI, UI, outcome history |
| Backups and trash | ✅ | create/restore/import/hash/verify, soft delete |
| Audit and logs | ✅ | sanitization, CSV, app/HTTP/security views |
| Notifications | ✅ | SMTP, ntfy, Discord, Telegram, webhook |
| Monitoring reports | ✅ | scheduled HTML reports; cron required |
| WAF | ✅ | detection, jail/ban, admin UI |
| GitHub content sync | ✅ partial toward target | content integration; not the full It.70 Git publisher |
| System update | ✅ | version check, tag deployment, optional webhook |
| Demo sandbox | ✅ | isolated demo mode and reset |
| Setup wizard | ⏳ | It.25 pre-Final; `first-run.sh` is current onboarding |
| Performance Guard | ⏳ | It.71 |

---

## 7. Extensions and Developer Mode

| Feature | Status |
|---------|--------|
| External plugin registry/runtime | ✅ |
| ZIP import and Zip-Slip protection | ✅ |
| Hook emitters | ✅ |
| Code Policy / security scanner | ✅ |
| Code Editor create/delete/restore | ✅ 🛠 |
| Custom editor components | ✅ |
| Blueprint manager | ✅ |
| Full theme import/runtime | 🟡 |
| Untrusted surfaces hardening | ⏳ It.67 |
| JSON Schema registry for admin writes | 🟡 | `settings.overrides@1` shipped It.68; Monaco/all document types → follow-ups |

---

## 8. Security baseline ✅ ongoing

- CSRF on mutating session endpoints.
- RBAC/ACL on protected operations.
- TOTP and optional workflow OTP.
- Encryption of sensitive settings/user fields with the application key.
- SSRF protection for outbound URLs.
- Path traversal, Zip-Slip, and media allow-list controls.
- WAF and login/OTP/newsletter rate limits.
- Log and CSV sanitization against injection.
- Demo mode fails closed outside its intended instance.
- Security audit and incident log.

The Hybrid Engine must not weaken this baseline. New drivers use the existing domain gates.

---

## 9. Hybrid Engine — planned capabilities

| Capability | Today | Target |
|------------|-------|--------|
| File SSOT | ✅ | preserve without exception |
| Content index | ✅ | settings + JSON content behind `StorageInterface` (It.68); full repo migration → follow-ups |
| File/memory cache | ✅ | unify in It.69 |
| Redis | ❌ | optional It.69 driver |
| HTTP validators | ❌ | `ETag`/`Last-Modified` in It.69 |
| Git publishing | 🟡 content sync only | immediate/queued in It.70 |
| APM | ❌ | Performance Guard in It.71 |
| S3 media | ❌ | It.72 |
| Multi-locale document | ❌ | It.73 |
| API keys/JWT | ❌ | It.74 |
| AI agent | ❌ | It.75 |
| Self-hosted translation | ❌ | It.76 |
| Cloud translation | ❌ | It.77 |

---

## 10. Quality and testing

The mandatory gate includes:

- PHP syntax and coding standard,
- PHPStan level 8,
- PHPUnit,
- TypeScript strict check,
- ESLint including the API barrel check,
- Vitest,
- API smoke, deployment smoke, and security packs where relevant.

Test counts change with every release; the canonical state is the current gate output, not a hard-coded number in documentation.

```bash
composer gate
cd frontend && npm run type-check && npm run lint && npm test
```

---

## 11. Snapshot boundaries

- Host cron must be configured on every instance; the existence of jobs alone is not enough.
- SSO, webhook deployment, and external connectors require correct secret configuration and outbound policy.
- Classic mode is the current safe base; It.68 foundation is in `[Unreleased]`; full Hybrid/Git-headless capability remains planned.
- The setup wizard, final theme model, and It.69–77 are not part of `beta.23`.
- Verify individual incident status in `ISSUES.md`; this overview is not an incident tracker.

---

## 12. Maintenance

Update this document for every release that:

- ships or removes a user capability,
- changes a `⏳/🟡/✅` state,
- adds a route module or admin/public route,
- changes the security baseline,
- changes Final 1.0 scope.
