# Security review guide (external auditors)

> **Target release:** `v2.1.0-beta.3` · tag **`v2.1.0-beta.3`** (recommended) · prior review baseline **`v2.1.0-beta.2`** · commit **`c68e72b`**  
> **Audience:** security professionals reviewing PaginiumCMS before / during Public Beta 1.

---

## Quick start (local lab)

```bash
git clone https://github.com/techberode/paginiumcms-architecture.git
cd paginiumcms-architecture
git checkout v2.1.0-beta.3
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
# Admin SPA (dev): docker compose --profile dev up -d → http://localhost:3025
```

Default admin (empty `data/users/` only):

| Field | Value |
|-------|--------|
| Email | `admin@localhost` |
| Password | `Admin123!ChangeMe` |

Override before first-run: `FIRST_ADMIN_EMAIL`, `FIRST_ADMIN_PASSWORD`, `FIRST_ADMIN_NAME`.

**Quality gate (same as maintainers):**

```bash
./scripts/iteration-gate.sh
composer audit
cd frontend && npm audit --audit-level=moderate
```

> **Note (beta.2):** Release **`v2.1.0-beta.2`** used `npm audit --audit-level=high` and reported 0 CVE. Three **moderate** React Router GHSA were published **after** that tag; fixed in **`v2.1.0-beta.3`** (ISS-078). Reviewers on beta.2 should read [Post-publication dependency disclosures](#post-publication-dependency-disclosures-after-v2110-beta2).

---

## Architecture (trust boundaries)

```mermaid
flowchart LR
  Browser[Browser / SPA] -->|HTTPS session cookie + CSRF| API[Slim API :8080]
  API --> Auth[AuthMiddleware / RBAC]
  API --> WAF[FirewallMiddleware]
  API --> CSRF[CsrfMiddleware]
  Auth --> Storage[(Flat files backend/storage/)]
  API --> Outbound[OutboundUrlGuard]
  Outbound --> OAuth[OAuth / webhooks / ntfy]
  Public[Anonymous visitor] -->|GET only| PublicAPI[/api/pages /articles /media]
  Public -->|GET allow-list| StorageMedia[/storage/app/content/media]
```

| Layer | Location | Notes |
|-------|----------|-------|
| HTTP entry | `backend/public/index.php` | Docroot must be `backend/public/` only |
| Middleware stack | `backend/bootstrap/app.php` | Order: security headers, WAF, rate limit, CSRF, … |
| Routes | `backend/app/Http/Routes/*.php` | Auto-discovered |
| Secrets on disk | `backend/storage/app/content/data/` | Outside docroot; encrypted fields use `enc:` prefix |
| Public file serving | `GET /storage/{path}` | Allow-list: `app/content/media`, `app/demo/media` only |

---

## Priority review areas

### 1. Authentication & session

| Item | File(s) | What to verify |
|------|---------|----------------|
| Session fixation | `SessionManager.php`, `AuthController.php` | `session_regenerate_id()` on login |
| Password storage | `UserRepository.php`, `PasswordPolicy.php` | Argon2id, no plaintext passwords |
| 2FA | `TwoFactorManager.php` | TOTP secret encrypted at-rest (`EncryptionService`) |
| Reset tokens | `UserRepository.php` | SHA-256 hash + `hash_equals`; no token in prod response |
| Login enumeration | `AuthController.php` | Generic reset message |
| Rate limits | `LoginRateLimitMiddleware.php`, `RateLimitMiddleware.php` | Brute-force / OTP limits |

### 2. Authorization (RBAC + Path ACL)

| Role | Typical access |
|------|----------------|
| `USER` | Read own profile; **no** content/media mutations |
| `EDITOR` | Content/media edit via `content:edit`, `media:*` |
| `ADMIN` | Admin modules, user management |
| `SUPER_ADMIN` | Security ACL, path ACL bypass |

Check: every **POST/PUT/PATCH/DELETE** in `Http/Routes/` has `AuthMiddleware` + `RoleMiddleware` or `PermissionMiddleware`.

Path ACL: `ContentPathAclGuard.php` — opt-in rules in `data/security/acl.json`.

### 3. CSRF

| Item | Detail |
|------|--------|
| Middleware | `CsrfMiddleware.php` |
| Token | `GET /api/auth/csrf-token` → session; sent as `X-CSRF-TOKEN` |
| Exempt prefixes | Pre-auth + anonymous public actions (login, register, contact, comments, maintenance) |
| FE | `frontend/src/api/client.ts`, `AuthContext.tsx` |

Test: mutating request without token → **403** `csrf_invalid` (except exempt paths).

### 4. Storage & path traversal

| Vector | Mitigation |
|--------|------------|
| `GET /storage/..` | `StorageController` allow-list + `realpath()` check |
| Content paths | `FileValidator`, `ContentPathAclGuard` |
| Code editor | `CodeEditorManager::normalizeRelativePath()` |
| Plugin ZIP | `PluginImporter::isSafeZipEntry()` (Zip-Slip) |
| Media upload | `UploadSecurityValidator`, SVG/HTML served as attachment |

**Historical critical (fixed):** unauthenticated `/storage/` exposing `data/users/*.json` — see ISS in `docs/ISSUES.md` (C-STORAGE class fix, 2.0.48 era).

### 5. SSRF (server-side request forgery)

Admin-configured outbound URLs must pass `OutboundUrlGuard`:

- `OAuthSsoService` (token + userinfo URLs)
- `NtfyAdapter`, `WebhookAdapter`, `DiscordAdapter`

Production: HTTPS only, block private/metadata IP ranges.

**Not guarded (by design):** fixed URLs — e.g. GeoIP `ip-api.com`, Unsplash stock import allow-list.

### 6. Plugins & code execution

| Control | File |
|---------|------|
| Forbidden constructs in extensions | `CodePolicyEngine.php`, `SecurityScanner.php` |
| Runtime load | `PluginManager.php` — `require_once` on vetted paths only |
| Import | `PluginImporter.php` — policy scan + Zip-Slip |

Focus: bypass of `EXTENSION_FORBIDDEN` (`include`, `eval`, `unserialize`, …).

### 7. WAF & logging

| Item | File |
|------|------|
| WAF | `FirewallMiddleware.php`, `config/firewall_scenarios.php` |
| Body scan | `FirewallBodyScanPolicy.php` (bounded; skips multipart / code-editor) |
| Log injection | `LogSanitizer.php` — used in access logs, firewall, **security audit CSV** |
| Audit trail CSV | `AuditTrailService::exportAuditToCsv()` — fixed in **beta.2** (ISS-077) |

### 8. Encryption at-rest

| Secret | Mechanism |
|--------|-----------|
| `twoFactorSecret` | `UserRepository` + `EncryptionService` |
| Settings passwords/tokens | `SettingsSchema::secretKeys()` + `SettingsRepository` |
| Key | `APP_KEY` in `.env` — **required** for production; placeholder disables encryption |

### 9. Public / anonymous endpoints

Review these **without** auth (CSRF exempt where noted):

| Method | Path | Risk focus |
|--------|------|------------|
| POST | `/api/auth/login`, `/register`, `/reset-password` | Rate limit, enumeration |
| POST | `/api/contact` | Spam, stored content in inbox |
| POST | `/api/comments` | Spam, XSS in moderation |
| POST | `/api/maintenance/*` | Maintenance mode gating |
| POST | `/api/debug/client-event` | No-op when `APP_DEBUG=false` |
| GET | `/api/settings/public` | No secrets; demo mode may show demo hints |
| GET | `/api/pages`, `/api/articles`, `/api/media` | Published content only |
| GET | `/storage/...` | Allow-list media only |

---

## Known open / low items (not blocking beta)

| ID | Topic | Notes |
|----|-------|-------|
| ISS-078 | React Router npm GHSA (post-beta.2) | ✅ Fixed **`v2.1.0-beta.3`** — see [below](#post-publication-dependency-disclosures-after-v2110-beta2) |
| ISS-008 | HTTPS | Transport — ops responsibility on production |
| ISS-011 | ESLint tech debt | CI baseline, not runtime security |
| ISS-014 | CORS | Must set `APP_ENV=production` on prod |
| S5 | CSP | `style-src 'unsafe-inline'` for React inline styles |
| S-DEMOCREDS | Demo login hints | ✅ Mitigated **`v2.1.0-beta.11`** — no password in public settings; `POST /api/demo/quick-login` |

Full public log: [ISSUES.md](ISSUES.md).

---

## Post-publication dependency disclosures (after v2.1.0-beta.2)

These advisories were **not public** when **`v2.1.0-beta.2`** was tagged (2026-07-23). They appeared in `npm audit` only at **`--audit-level=moderate`**, after GitHub published GHSA entries.

| When | Package (locked) | Finding | Status |
|------|------------------|---------|--------|
| Post-beta.2 | `react-router-dom@6.30.4` / `react-router@6.30.4` | 3× moderate — open redirect (×2), SSR `deserializeErrors()` | ✅ **`react-router-dom@7.18.1`** in **`v2.1.0-beta.3`** |

### Advisory links (reviewer copy-paste)

| GHSA | Title | URL |
|------|-------|-----|
| GHSA-wrjc-x8rr-h8h6 | Open redirect via backslash in `<Link>` and `useNavigate` (CVE-2025-68470 bypass) | https://github.com/advisories/GHSA-wrjc-x8rr-h8h6 |
| GHSA-jjmj-jmhj-qwj2 | Open redirect leading to XSS (`react-router-dom`) | https://github.com/advisories/GHSA-jjmj-jmhj-qwj2 |
| GHSA-337j-9hxr-rhxg | Arbitrary Constructor Injection via `deserializeErrors()` (SSR hydration) | https://github.com/advisories/GHSA-337j-9hxr-rhxg |

**PaginiumCMS context:**

- **Application type:** client-side SPA (`BrowserRouter` in `frontend/src/main.tsx`) — no React Router SSR hydration in Beta 1.
- **Open redirect:** relevant only if untrusted strings reach `Link`/`navigate` `to` props; admin routes are static; public slugs come from CMS content (still sanitize user-authored links in content).
- **Fix:** dependency bump only; API unchanged for our usage (`Routes`, `Link`, `useNavigate`, …).

Public incident: **ISS-078** in [ISSUES.md](ISSUES.md).

---

## Suggested test checklist

| # | Test | Expected |
|---|------|----------|
| 1 | `GET /storage/app/content/data/users/` (or traversal variant) | **404** |
| 2 | USER role `POST /api/pages` | **403** |
| 3 | `POST /api/admin/settings` without CSRF | **403** |
| 4 | Login with wrong password 20× | **429** / lockout |
| 5 | Register with `password` ≠ `passwordConfirm` | **422** |
| 6 | Import plugin ZIP with `../` path | **Rejected** |
| 7 | Import plugin with `eval()` in PHP | **Rejected** |
| 8 | OAuth redirect_uri mismatch | **Auth failure** |
| 9 | Webhook URL `http://169.254.169.254/` in settings (prod) | **Blocked** by guard |
| 10 | Export security audit CSV with `\r\n` in message | Single line cells (sanitized) |

Automated coverage: `backend/tests/Http/Middleware/CsrfMiddlewareTest.php`, `OutboundUrlGuardTest.php`, `PathAclIntegrationTest.php`, `PluginImporterTest.php`, `EncryptionServiceTest.php`, `AuditTrailServiceTest.php`.

---

## Reporting findings

See root [SECURITY.md](../SECURITY.md) — **private** report first; reference version **`v2.1.0-beta.3`** (or **`v2.1.0-beta.2`** if reviewing the pre-patch baseline).

Thank you for reviewing PaginiumCMS.
