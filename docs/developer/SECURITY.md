# Security architecture — PaginiumCMS

> **Release:** `v2.1.0-beta.3` · Maintainer reference + pointer for [SECURITY_REVIEW.md](../SECURITY_REVIEW.md).

---

## Threat model (Beta 1)

| Actor | Goal | Primary controls |
|-------|------|------------------|
| Anonymous internet | Read published content; abuse public forms | Rate limits, WAF, maintenance mode, comment toggles |
| Registered USER | Escalate to editor/admin | RBAC, permission middleware |
| EDITOR | Access other tenants' paths (single-tenant) | Path ACL, content locks |
| ADMIN | Misconfigure outbound URLs → SSRF | `OutboundUrlGuard` |
| ADMIN | Upload malicious plugin | Code policy, Zip-Slip, sandboxed extension dir |
| Network attacker | Session hijack, CSRF | HttpOnly session, SameSite, CSRF token |
| File reader | Steal `data/users/*.json` via HTTP | Storage allow-list; data outside docroot |

Single-tenant assumption: one CMS instance per deployment. Multi-tenant isolation is **not** a Beta 1 goal.

---

## Middleware pipeline

Registered in `backend/bootstrap/app.php` (order matters):

1. **SecurityMiddleware** — CSP, HSTS, `X-Frame-Options`, `nosniff`
2. **FirewallMiddleware** — scenario-based WAF (URI, query, UA, optional JSON body)
3. **RateLimitMiddleware** — global per-IP budget
4. **LoginRateLimitMiddleware** — `/api/auth/login`
5. **Otp*RateLimitMiddleware** — registration/workflow OTP
6. **CsrfMiddleware** — POST/PUT/PATCH/DELETE (disabled in `APP_ENV=testing`)
7. **MaintenanceModeMiddleware** — blocks public API when enabled
8. Per-route: **AuthMiddleware**, **RoleMiddleware**, **PermissionMiddleware**, **TwoFactorMiddleware**

---

## Authentication

| Component | Path |
|-----------|------|
| Session | `Modules/Security/Services/SessionManager.php` |
| Login/logout/register | `Http/Controllers/Auth/AuthController.php` |
| 2FA setup/verify | `Http/Controllers/Auth/TwoFactorController.php` |
| Password policy | `SettingsBackedPasswordPolicy.php` |
| Password confirm | `Support/ValidationRules.php` — register + admin user CRUD |
| SSO (optional) | `OAuthSsoService.php` — `hash_equals` on state/redirect_uri; JIT role max `EDITOR` |
| Login lockout | `LoginAttemptTracker.php` |

Session cookie: HttpOnly, SameSite=Lax (see `bootstrap/session.php`).

---

## Authorization

| Mechanism | Usage |
|-----------|--------|
| `RoleMiddleware` | Admin modules — roles `EDITOR`, `ADMIN`, `SUPER_ADMIN` |
| `PermissionMiddleware` | Fine-grained — e.g. `content:edit`, `content:delete`, `media:upload` |
| `PermissionCatalog.php` | Canonical permission strings |
| `PathAclService` + `ContentPathAclGuard` | Optional path rules in `data/security/acl.json` |
| `AccessControlSyncService` | Settings UI ↔ flat-file ACL |

`SUPER_ADMIN` bypasses Path ACL. Role assignment blocked for escalating to `SUPER_ADMIN` without super-admin actor (`UserController`).

---

## CSRF

**Pattern:** synchronizer token (not double-submit cookie alone).

1. `GET /api/auth/csrf-token` stores token in session.
2. SPA sends `X-CSRF-TOKEN` on mutating requests.
3. `CsrfMiddleware` verifies via `CsrfProtectionManager` + `hash_equals`.

**Exempt** (see `CsrfMiddleware::EXEMPT_PREFIXES`): pre-auth auth routes, public contact/comments/maintenance, debug client-event.

Frontend: token fetched at bootstrap; retry on `403 csrf_invalid` (`frontend/src/api/client.ts`).

---

## Data protection at-rest

`Core/Security/Services/EncryptionService.php`:

- Key: 32-byte `APP_KEY` from `.env`
- Algorithms: libsodium `secretbox` (`enc:s1:`) or OpenSSL AES-256-GCM (`enc:g1:`)
- Transparent read: plaintext legacy values still work; writes encrypt when key valid

Encrypted fields:

- User: `twoFactorSecret`
- Settings: keys from `SettingsSchema::secretKeys()` (SMTP, webhooks, SSO client secrets, …)

---

## Public storage

`Http/Controllers/Storage/StorageController.php`:

- Allowed prefixes: `app/content/media`, `app/demo/media`
- Denied: `data/`, `logs/`, `backups/`, `dev/`, `cache/`
- SVG/HTML/XML: `Content-Disposition: attachment`, CSP `sandbox`

Physical layout: `backend/storage/` is **sibling** of `backend/public/` — not web-root reachable except via controlled route.

---

## SSRF

`Core/Security/Services/OutboundUrlGuard.php`:

- Production: HTTPS only; resolve host to IP; reject private/reserved ranges
- Development/testing: relaxed for local OAuth/ntfy testing

Applied in: OAuth token/userinfo fetch, notification adapters.

**Fixed URLs** (not admin-controlled): stock image import (Unsplash hosts only), GeoIP lookup.

---

## WAF

`Core/Security/Firewall/`:

- Scenarios in `config/firewall_scenarios.php`
- SQLi / path traversal / SSRF probes in URI, query, UA, and bounded POST JSON
- Body scan skippable for multipart uploads and code-editor API (`FirewallBodyScanPolicy`)
- Toggle: settings / `firewall.scanRequestBody`
- Testing env: middleware bypass for PHPUnit

Admin: `/api/admin/firewall/*` — ban list, whitelist, incident log.

---

## Plugins & Code Editor

| Surface | Controls |
|---------|----------|
| ZIP import | Entry path validation, `CodePolicyEngine`, `SecurityScanner` |
| Runtime | Hooks only from registered extensions under controlled directory |
| Code editor | Path normalization, `realpath` jail, syntax check, backup before save |
| Forbidden in extensions | `eval`, `exec`, `include/require`, `unserialize`, `call_user_func*` |

Policy doc: [EXTENSION_CODE_POLICY.md](./EXTENSION_CODE_POLICY.md).

---

## Logging & audit

| Sink | Sanitization |
|------|----------------|
| Access logs | `LogSanitizer` on untrusted fields |
| Firewall incidents | `LogSanitizer` |
| Security audit CSV | `SecurityAuditStore::exportCsv()` |
| Audit trail CSV | `AuditTrailService::exportAuditToCsv()` — **beta.2** aligned with C11 |

Security audit UI: `/api/admin/security/audit` (ADMIN+).

---

## Developer mode

Gated by TOTP + `DEV_UNLOCK_SECRET` (no predictable fallback in production). Routes under `/api/admin/developer/*`. See [user/DEVELOPER_MODE.md](../user/DEVELOPER_MODE.md).

---

## Dependency & CI security

- `composer audit` in CI
- `npm audit --audit-level=high` in CI (legacy gate — **moderate** FE advisories can slip through; see ISS-078)
- **Maintainer pre-release:** also run `npm audit --audit-level=moderate` (required since post-beta.2 React Router GHSA)
- PHPStan level 8 on `backend/app`
- PHPUnit security regression suites (CSRF, Path ACL, plugins, encryption, OutboundUrlGuard)

### Post-beta.2 npm disclosure (ISS-078)

After tag **`v2.1.0-beta.2`**, GitHub published three **moderate** React Router advisories affecting `react-router-dom@6.30.4`. No 6.x patch exists; fix = **`react-router-dom@7.18.1`**.

| GHSA | Summary | Link |
|------|---------|------|
| GHSA-wrjc-x8rr-h8h6 | Open redirect (backslash bypass, CVE-2025-68470) | https://github.com/advisories/GHSA-wrjc-x8rr-h8h6 |
| GHSA-jjmj-jmhj-qwj2 | Open redirect → XSS | https://github.com/advisories/GHSA-jjmj-jmhj-qwj2 |
| GHSA-337j-9hxr-rhxg | `deserializeErrors()` constructor injection (SSR) | https://github.com/advisories/GHSA-337j-9hxr-rhxg |

PaginiumCMS Beta 1 is **SPA-only** — SSR hydration issue is informational. Fixed in **`v2.1.0-beta.3`**. Detail: [ISSUES.md](../ISSUES.md#iss-078--react-router-npm-advisories-post-beta2--vyriešené-2110-beta3) · [SECURITY_REVIEW.md](../SECURITY_REVIEW.md#post-publication-dependency-disclosures-after-v2110-beta2).

---

## Related documents

| Doc | Content |
|-----|---------|
| [SECURITY_REVIEW.md](../SECURITY_REVIEW.md) | External auditor test plan |
| [ISSUES.md](../ISSUES.md) | Public incident log |
| [CORE_HARDENING.md](../architecture/CORE_HARDENING.md) | RBAC, maintenance, trash |
| [ACCESS_CONTROL.md](../user/ACCESS_CONTROL.md) | User-facing ACL guide |
| [EXTENSION_CODE_POLICY.md](./EXTENSION_CODE_POLICY.md) | Plugin security rules |
| [BETA_INFRA.md](./BETA_INFRA.md) | Beta security baseline checklist |

Private detailed incident log: `SECURITY_ISSUES.md` (maintainer local, gitignored).
