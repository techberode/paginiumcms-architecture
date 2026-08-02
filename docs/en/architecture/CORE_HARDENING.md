---
title: Core hardening
description: Security and operational invariants of PaginiumCMS
icon: material/shield-lock-outline
---

# PaginiumCMS Core Hardening

> **Checkpoint:** `v2.1.0-beta.23`  
> **Rule:** a security control is effective only when enforced on the server and tested.

This document consolidates Core protections and extends them for the Hybrid Engine direction. It is not a replacement for a threat model or deployment hardening; it defines minimum invariants that controllers, modules, drivers, extensions, and external clients must preserve.

---

## 1. Request pipeline

The current bootstrap documents an approximate order:

```text
CORS / security headers
→ maintenance + locale
→ WAF
→ global rate limit
→ analytics/request logging
→ route authentication
→ role/permission/2FA/developer gates
→ controller/domain validation
→ unified error handler
```

Actual Slim middleware order may be affected by LIFO behavior. Integration tests must therefore verify observed order, not merely the order of `add()` calls.

WAF is intended to run before rate limiting, but neither layer replaces permission and schema validation.

---

## 2. Authentication

### Administration

The current primary model is a server-side session, secure cookie, synchronizer CSRF token, and TOTP for protected areas. Session ID is regenerated on login.

Required properties:

- `HttpOnly`, `Secure` in HTTPS production, and appropriate `SameSite`,
- session fixation protection,
- timeout and explicit logout/invalidation,
- login rate limit and audit without password/TOTP,
- recovery/2FA flows with equivalent protection,
- no session or CSRF tokens in URLs.

### Headless clients

It.74 adds API keys and short-lived JWT as an additive model. Tokens require scopes, expiry, rotation, revocation, and audit. The browser admin must not switch to a long-lived Bearer token in `localStorage` merely to simplify implementation.

---

## 3. Authorization

Every mutation checks a domain permission, not only button visibility. Indicative mapping:

| Operation | Permission |
|-----------|------------|
| create content | `content:create` |
| edit content | `content:edit` |
| delete content | `content:delete` |
| upload/delete media | `media:upload` / `media:delete` |
| firewall/log/settings administration | admin/restricted permission |
| access-control change | SUPER_ADMIN or dedicated permission |
| developer code write | developer unlock + code policy + permission |

An `ADMIN` manage permission may cover domain actions, but implementation must use one permission resolver. A driver or background job receives an actor/service identity and may not run as an unlimited “system superuser” without scope.

Path ACL applies to the canonical content key/path and must behave the same in UI, REST API, queue, and restore.

---

## 4. CSRF, CORS, and cookies

- Session-authenticated mutations require CSRF.
- A cookie-free Bearer client does not use CSRF but still passes scopes and rate limits.
- Production CORS is an explicit allow-list, not reflection of arbitrary `Origin`.
- Preflight cannot expose protected credentials to disallowed origins.
- Cookie domain/path remain as narrow as practical.
- `Access-Control-Allow-Credentials` is never combined with wildcard origin.

---

## 5. Input, schema, and output

Every input has a size limit, type contract, and normalization. Key domains include:

- slug/path and filenames,
- Markdown/front matter/JSON Schema,
- URLs for webhooks, imports, stock media, Git, and provider APIs,
- upload MIME/extension/size,
- locale, timezone, enums, and retention,
- pagination and filter bounds.

Output encoding depends on context. Markdown rendering is sanitized; logs sanitize CR/LF/ANSI; CSV export protects against formula injection; production JSON errors do not expose stack traces.

---

## 6. Storage and media hardening

- allow-listed storage roots,
- path traversal and symlink-escape protection,
- atomic writes and safe permissions,
- web denial for `data`, logs, backups, cache, firewall, and dev stores,
- HTML/SVG media served as attachment or under sandbox policy,
- no PHP/executable upload into a web-served path,
- private-media URL policy in It.72,
- checksum and journal for driver migration.

See [STORAGE.md](./STORAGE.md).

---

## 7. WAF

`FirewallMiddleware` and related services scan scenarios, manage bans/SIN scores, and record incidents. Flow:

1. trusted client IP is resolved only with correct `TRUSTED_PROXIES`,
2. explicit allow-list is used cautiously and audited,
3. active ban/jail returns 403,
4. pattern match creates an incident and possible escalation,
5. request proceeds only after passing.

WAF rules require tests for false positives and encoding bypasses. Although the CMS does not use SQL, an SQLi pattern can still indicate hostile input probing.

The test environment may disable WAF for most tests, but a dedicated WAF suite remains mandatory.

---

## 8. Rate limiting and abuse controls

Global, login, and per-route limits use canonical client identity. Sensitive endpoints have dedicated budgets:

- login/reset/OTP resend,
- comments/contact/register,
- import/upload,
- translation/AI/provider calls,
- API-key creation and token exchange,
- expensive search/rebuild/admin export.

The rate-limit store is derived operational state. Redis failure must not create an unlimited mode without an incident; fallback policy is explicit and appropriate for the deployment profile.

---

## 9. Maintenance and availability

Maintenance mode returns 503 for public APIs according to policy while preserving health/auth/admin recovery paths needed for repair. Exceptions are allow-listed rather than broad prefixes that might accidentally expose a new endpoint.

Authenticated staff preview may remain available, but public cache must not store a staff-only response.

---

## 10. Secrets

- sensitive settings fields are encrypted at rest,
- the master key is not in the repository or settings JSON,
- token comparison uses constant-time functions,
- credentials are not displayed after save,
- webhook/API/provider logs redact headers and query secrets,
- backup and restore document key dependency,
- rotation uses versioned ciphertext and rollback,
- Git commit/publish never includes `.env`, private keys, or storage secrets.

---

## 11. Outbound requests and SSRF

Every outbound connector, webhook, Git callback, media import, translation, or AI provider uses a shared URL policy:

- permit `https` and explicitly documented exceptions,
- DNS/IP checks against loopback, link-local, private, and metadata networks according to policy,
- validate every redirect hop,
- enforce timeout, response-size, and content-type limits,
- send credentials only to the expected host,
- audit provider/host without secret query values.

A self-hosted provider may require a private LAN address. This is an explicit admin allow-list exception, not a reason to disable SSRF protection globally.

---

## 12. Developer Mode and extension code

Developer Mode is locked by default even under debug configuration. Unlock requires TOTP or an offline developer token, has TTL, and is audited. The code editor:

- writes only to allowed roots,
- creates backup/diff,
- runs syntax checks and `CodePolicyEngine`,
- blocks dangerous constructs according to policy,
- does not give extensions shell or generic filesystem access,
- does not treat Apply as automatic publish/deploy.

ZIP import prevents Zip-Slip, limits file count/size, and validates the manifest before moving code into a runtime root.

---

## 13. Events, queues, and background jobs

A job carries actor/service identity, idempotency key, minimal payload, and deadline/retry policy. Secrets are referenced by safe ID rather than copied into queue JSON.

A listener/job cannot bypass permission, path validation, or schema simply because it did not arrive through HTTP. Poison jobs enter a dead-letter/failed state; infinite retry is forbidden.

An `after_save` failure after a successful write cannot claim rollback. Publishing, translation, and AI states are tracked separately.

---

## 14. Logging and audit

| Layer | Purpose |
|-------|---------|
| HTTP access log | method, route template, status, duration, request ID, redacted client context |
| Application log | technical error and incident ID |
| Security log | auth/WAF/rate/permission events |
| Audit trail | who, what, which object, before/after metadata without secrets |
| Event/job log | event/job ID, handler, outcome, retry |

Log injection is sanitized. Retention, rotation, and permissions are settings/deployment policy. Auth bodies, cookies, Authorization headers, TOTP, reset tokens, and plaintext credentials are never logged.

---

## 15. Soft delete, restore, and destructive actions

Trash operations validate origin path and conflicts. Bulk restore/purge has count limits, permission, and audit. Permanent purge, key rotation, driver migration, extension install, and access-control changes require explicit confirmation; the most sensitive workflows may require OTP approval.

---

## 16. Hybrid Engine security gates

| Iteration | Required gate |
|-----------|---------------|
| It.68 | driver parity, path tests, schema validation, migration journal |
| It.69 | cache poisoning/tenant-locale keys, Redis TLS/auth, safe fallback |
| It.70 | Git command safety, secret exclusion, controlled remote policy, idempotent publish |
| It.71 | metrics without PII/secrets, only allow-listed reversible self-healing |
| It.72 | private/public media policy, checksum migration, SSRF for import |
| It.73 | locale ACL/revision integrity and fallback without data leakage |
| It.74 | scoped keys/JWT, rotation, revocation, no browser localStorage |
| It.75 | prompt-injection isolation, allow-listed schema tools, human Apply, no autonomous publishing |
| It.76–77 | proposal/diff/Apply, quota, secret provider config, no auto-publish |

---

## 17. Test gate

- auth/session/CSRF fixation and expiry,
- RBAC/path ACL for HTTP, queue, and restore,
- WAF bypass/false-positive tests,
- rate limiting and trusted-proxy spoofing,
- path traversal/symlink/Zip-Slip/upload polyglot,
- SSRF redirect/DNS-rebinding scenarios where supported by the harness,
- secret redaction in API/log/export,
- broken/readonly/disk-full storage,
- extension/AI tool sandbox boundaries,
- Classic smoke test without external capabilities,
- dependency audit, PHPStan L8, PHPUnit, TypeScript, ESLint, and Vitest.

---

## Related documents

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [CORE.md](./CORE.md)
- [STORAGE.md](./STORAGE.md)
- [SETTINGS.md](./SETTINGS.md)
- [EVENTS.md](./EVENTS.md)
- [API_CONTRACT.md](./API_CONTRACT.md)
- [../user/FIREWALL.md](../user/FIREWALL.md)
- [../user/LOGGING.md](../user/LOGGING.md)
