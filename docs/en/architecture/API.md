---
title: API Reference
description: Canonical overview of the PaginiumCMS HTTP API
icon: material/api
---

# 🔌 PaginiumCMS API Reference

> **Document status:** Public Beta · checkpoint `v2.1.0-beta.23` · 2 August 2026  
> **Backend:** PHP 8.4+ · Slim 4 · JSON REST API  
> **Response contract:** [API_CONTRACT.md](./API_CONTRACT.md)

This document is an **overview of the public HTTP surface**, not a generated OpenAPI specification. It separates routes confirmed by the supplied documentation checkpoint, transitional legacy behavior, and planned Hybrid Engine capabilities. Before a release, the inventory must be compared with the current route registry and contract tests; a historical document or frontend client alone is not proof that a route still exists.

---

## 1. API rules

1. The React administration and external headless clients use the same application services and domain rules.
2. An HTTP controller must not write directly to files or bypass RBAC, path ACL, revision, or lock checks.
3. The admin session flow remains cookie-based and uses CSRF for mutations. It.74 API keys/JWT are a planned additive capability, not a replacement for the admin session.
4. A public client never automatically receives drafts, private media, secret settings, or admin metadata.
5. Primary save and optional Git/translation/AI follow-up have separate states. `stored` does not automatically mean `pushed`.
6. The API must work in the Classic profile without Redis, S3, a Git remote, an LLM, or cloud translation.
7. An endpoint, payload, or enum is not stable merely because it appears in a historical iteration document.

### Status markers

| Marker | Meaning |
|--------|---------|
| ✅ | confirmed as implemented at the documentation checkpoint |
| 🟡 | implemented with a legacy/transitional contract or pending consolidation |
| ⏳ | planned in It.68–77; clients must not depend on it yet |
| 🧪 | diagnostic or environment-gated behavior |

---

## 2. Base URL, formats, and headers

A typical backend base URL uses the same origin as the administration, for example:

```text
https://cms.example.com/api
```

JSON requests use:

```http
Content-Type: application/json
Accept: application/json
```

Multipart uploads use `multipart/form-data`. RSS, sitemap, `robots.txt`, binary media, and some WAF blocks are not JSON endpoints.

Recommended request headers by flow:

| Header | Use |
|--------|-----|
| `X-CSRF-Token` | session-authenticated mutations |
| `Authorization: Bearer …` | ⏳ It.74 API key/JWT flow; not admin SPA local storage |
| `If-None-Match` | ✅ conditional reads on public GET (`/api/settings/public`, `/api/pages`, `/api/articles`) — It.69 |
| `If-Match` or `baseRevision` in payload | OCC writes; the wire contract must remain consistent |
| `Idempotency-Key` | ⏳ publish/job mutations that may be retried safely |
| `Accept-Language` or explicit `locale` | ⏳ locale-aware content after It.73; fallback must not bypass ACL |

The API uses UTF-8 and ISO 8601/RFC 3339 timestamps with a timezone. Clients must not parse localized display dates as a machine contract.

---

## 3. Authentication matrix

| Client | Mechanism | CSRF | Authorization | Status |
|--------|-----------|------|---------------|--------|
| anonymous browser/headless client | no identity | no | public slice only | ✅ |
| React admin SPA | secure PHP session cookie | yes for mutations | RBAC + path ACL + optional 2FA/OTP | ✅ |
| server integration/CI | scope-limited API key | no when cookies are not authoritative | route/method allow-list + scopes | ⏳ It.74 |
| short delegated task | short-lived JWT | no | issuer/audience/scopes/expiry | ⏳ It.74 |
| background job | service/actor identity | no | minimum explicit scope | target contract |

An invalid Bearer token must not silently fall back to session or anonymous access. Storing an admin bearer token in `localStorage` is a prohibited direction.

---

## 4. Public and system endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| `GET` | `/api/health` | basic health check without sensitive details | ✅ |
| `GET` | `/api/settings/public` | allow-listed public settings slice | ✅ |
| `GET` | `/api/validation/rules` | shared validation rules for UX; backend remains authoritative | ✅ |
| `GET` | `/feed.xml` | RSS 2.0 for published articles | ✅ |
| `GET` | `/sitemap.xml` | sitemap for published pages and articles | ✅ |
| `GET` | `/robots.txt` | crawler policy and sitemap reference according to settings | ✅ |
| `GET` | `/storage/{path}` | policy-approved public media/static files; traversal must be blocked | ✅ |
| `POST` | `/api/debug/client-event` | client diagnostics; safely disabled outside an allowed mode | 🧪 |

Anonymous `/api/health` output must not expose stack traces, filesystem paths, credentials, internal hostnames, or full provider configuration. Extended health/APM data belongs in protected admin APIs.

---

## 5. Public content and communication

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| `GET` | `/api/pages` | page list; anonymous clients see published content only | ✅ |
| `GET` | `/api/pages/{slug}` | page detail | ✅ |
| `GET` | `/api/articles` | article list with filters and pagination | ✅ |
| `GET` | `/api/articles/{slug}` | article detail | ✅ |
| `GET` | `/api/search` | public search or protected admin palette search | ✅ |
| `GET` | `/api/seo/{type}/{slug}` | normalized SEO metadata for published content | ✅ |
| `GET` | `/api/navigation` | public navigation tree | ✅ |
| `GET` | `/api/comments` | approved comments according to policy | ✅ |
| `POST` | `/api/comments` | comment submission with rate limiting/moderation | ✅ |
| `POST` | `/api/contact` | contact form with anti-abuse controls | ✅ |
| `POST` | `/api/analytics/pageview` | privacy-aware pageview ingest when analytics is enabled | ✅ by module |
| `POST` | `/api/newsletter/subscribe` | newsletter opt-in when the capability is deployed | ✅ by module |
| `GET` | `/api/gallery/public` | public gallery slice when the module is enabled | ✅ by module |

Exact query parameters, public rules, and write lifecycle are defined in [CONTENT_API.md](./CONTENT_API.md).

---

## 6. Authentication and account

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| `POST` | `/api/auth/login` | login; may continue to a 2FA challenge | ✅ · legacy envelope |
| `POST` | `/api/auth/register` | registration according to settings and OTP policy | ✅ · environment policy |
| `POST` | `/api/auth/logout` | terminate the session | ✅ |
| `GET` | `/api/auth/me` | current session identity and client-required capabilities | ✅ |
| `GET` | `/api/auth/csrf-token` | CSRF synchronizer token | ✅ |
| `POST` | `/api/auth/change-password` | password change | ✅ |
| `POST` | `/api/auth/reset-password` | start reset flow without user enumeration | ✅ |
| `POST` | `/api/auth/verify-reset-token` | complete password reset | ✅ |
| `GET` | `/api/auth/sso/providers` | enabled SSO providers without secrets | ✅ when configured |
| `GET` | `/api/auth/sso/{provider}/start` | OAuth/OIDC start with validated state/redirect | ✅ when configured |
| `GET` | `/api/auth/sso/{provider}/callback` | callback and session creation | ✅ when configured |

Authentication endpoints historically use a flat success envelope. New code must not spread that legacy shape into additional domains; migration rules are in [API_CONTRACT.md](./API_CONTRACT.md).

---

## 7. Authenticated content workflow

The following families use session + CSRF + permission/path ACL and may later support explicit It.74 write scopes:

| Family | Typical operations | Protection |
|--------|--------------------|------------|
| `/api/pages`, `/api/articles` | create, update, status/publish, soft delete, bulk operations | `content:*`, revision, lock, schema |
| `/api/media/*` | upload, list, folder/metadata, delete, stock import | `media:*`, MIME/size/path policy |
| `/api/drafts/{type}/{slug}` | read/save/delete autosave draft | owner/editor policy + base revision |
| `/api/locks/*` | acquire/heartbeat/release | actor identity + expiry |
| `/api/workflows/otp/*` | verify/resend a sensitive action | challenge owner, TTL, attempt limit |

A sensitive mutation may first return an OTP challenge. The client must not report the publish as complete until the verification response confirms the result.

---

## 8. Admin API families

Most `/api/admin/*` endpoints require at least ADMIN and, according to policy, recent 2FA. Exact permissions must be a route-level allow-list, not merely a prefix check.

| Area | Prefix / example | Notes |
|------|------------------|-------|
| settings | `/api/admin/settings` | schema-driven; secret values are redacted/write-only |
| users and access | `/api/admin/users`, `/api/admin/security/*` | SUPER_ADMIN-only operations must be explicit |
| trash | `/api/admin/trash/*` | restore, purge, backup; permanent deletion is separate |
| backups | `/api/admin/backups/*` | create/list/verify/restore/import according to policy |
| versions and conflicts | `/api/admin/versions/*`, `/api/admin/conflicts` | compare/restore/cleanup |
| audit and logs | `/api/admin/audit/*`, `/api/admin/logs*` | exports redact secrets and prevent formula injection |
| dashboard/counts | `/api/admin/dashboard/overview`, `/api/admin/counts` | aggregated UI data, not an authorization source |
| analytics | `/api/admin/analytics/*` | protected metrics and retention |
| firewall | `/api/admin/firewall/*` | bans, whitelist, incidents, stats |
| content utility | `/api/admin/content/*` | e.g. SEO audit/suggestions; no auto-publish without policy |
| comments/messages | `/api/admin/comments/*`, `/api/admin/messages/*` | moderation and bulk workflows |
| navigation | `/api/admin/navigation` | validated tree and audit |
| notifications | `/api/admin/notifications/*` | connector testing without credential leakage |
| jobs | `/api/admin/jobs/*` | scheduler/queue; service identity and idempotency |
| code editor/developer | `/api/admin/code-editor/*`, `/api/admin/developer/*` | developer gate, code policy, path allow-list |
| extensions | `/api/admin/extensions/*` | ZIP policy scan, manifest, enable/disable |
| blueprints | `/api/admin/blueprints/*` | schema CRUD and sample validation |
| demo | `/api/admin/demo/*` | demo capability only; safely disabled in production |
| Git/publish | `/api/admin/git/*` | 🟡 existing sync; target immediate/queued publish in It.70 |

This is a family-level overview. A release-grade reference requires a generated route inventory/OpenAPI file or a test that compares documentation with the registry.

---

## 9. Hybrid Engine API extensions

| Iteration | Expected API surface | Status |
|-----------|----------------------|--------|
| It.68 | `GET /api/admin/settings/engine` returns schema + `meta.capabilityProbe`; invalid overrides → **422**; only `classic`/`local` active | ✅ |
| It.69 | cache status/rebuild and HTTP validators; Redis remains optional | ⏳ |
| It.70 | `/api/admin/git/publish`, status/job detail, retry | ⏳ consolidation |
| It.71 | protected APM metrics such as `/api/admin/metrics/apm` | ⏳ |
| It.72 | media driver capability and local/S3 migration | ⏳ |
| It.73 | locale-aware content read/write, explicit fallback, and revision | ⏳ |
| It.74 | API key lifecycle and short-lived JWT | ⏳ |
| It.75 | AI proposal/tool workflow; human Apply and no autonomous publish | ⏳ |
| It.76–77 | translation proposal/diff/Apply and provider status/quota | ⏳ |

A route name in a historical iteration is not automatically final. Before implementation it must pass threat modeling, naming review, and contract testing.

### It.68 — engine settings and capability probe (shipped)

**Endpoint:** `GET /api/admin/settings/engine`  
**Auth:** session + CSRF bootstrap; **SUPER_ADMIN** for engine group read/write.

The response follows the standard admin settings group shape (`schema`, `values`, `meta`). When `engine.capabilityProbeEnabled` is true (default), `meta` includes:

```json
{
  "capabilityProbe": {
    "deploymentMode": { "configured": "classic", "active": "classic", "status": "active" },
    "storageDriver": { "configured": "local", "active": "local", "status": "active" },
    "capabilities": {
      "localStorage": { "status": "available", "message": "Local flat-file storage is operational." },
      "classicMode": { "status": "available", "message": "Classic deployment mode is active." },
      "hybridMode": { "status": "unavailable", "message": "Hybrid mode is not installed in this release." },
      "gitHeadlessMode": { "status": "unavailable", "message": "Git headless mode is not installed in this release." },
      "remoteStorageDrivers": { "status": "unavailable", "message": "Only the local driver is available in Iteration 68." },
      "schemaValidation": { "status": "available", "message": "JSON Schema validation for admin documents." }
    }
  },
  "documentationUrl": "/docs/en/architecture/HYBRID_ENGINE.md"
}
```

The probe reports capability status only — no internal filesystem paths. When the probe is disabled, `meta.capabilityProbe` is `null`.

**Validation:** settings overrides are validated against `settings.overrides@1` before and after mutation. Corrupt group shapes (for example `"general": "not-an-object"`) return HTTP **422** with stable field errors instead of silent normalization. Non-Classic deployment modes and non-local storage drivers are rejected until their iterations ship.

See [SETTINGS](./SETTINGS.md), [STORAGE](./STORAGE.md), and [ITERATION_68](../ITERATION_68.md).

---

## 10. Request and response examples

### Public list

```bash
curl --fail-with-body \
  'https://cms.example.com/api/articles?page=1&per_page=20&status=published'
```

### Session mutation

```bash
curl --fail-with-body \
  --cookie cookies.txt \
  -H 'Content-Type: application/json' \
  -H 'X-CSRF-Token: <csrf-token>' \
  -X PUT \
  --data '{"title":"About us","content":"...","baseRevision":"<revision>"}' \
  'https://cms.example.com/api/pages/about-us'
```

### Planned API key read

```bash
curl --fail-with-body \
  -H 'Authorization: Bearer pgk_<id>_<secret>' \
  'https://cms.example.com/api/articles?status=published'
```

Real tokens, session cookies, and CSRF tokens do not belong in Git, issue reports, or documentation screenshots.

---

## 11. Compatibility and deprecations

- The legacy auth envelope is supported only for the existing frontend flow; new clients must use a centralized parser.
- A legacy list without `page/per_page` may return all items without `meta`; new UIs should request explicit pagination.
- Query aliases such as `perPage` and `per_page` must not expand indefinitely. One canonical name is documented and aliases receive a deprecation plan.
- A breaking payload change requires versioning or a coordinated client migration, contract tests, and a changelog entry.
- Clients should ignore unknown response fields when they do not change security semantics. Unknown write fields should be rejected by schema or explicitly ignored, never silently persisted.

---

## 12. Testing and release gate

Minimum gate:

1. route inventory or OpenAPI diff against the previous release,
2. contract tests for success/error/validation/409/auth/OTP/WAF exceptions,
3. session + CSRF + RBAC/path ACL tests,
4. a public endpoint test proving drafts and secret settings are not exposed,
5. upload abuse, path traversal, and oversized-body tests,
6. Postman/Newman smoke coverage for a representative public flow,
7. frontend MSW/Vitest tests using matching fixtures,
8. Classic profile test without optional providers.

The current Postman collection is only a small smoke subset and must not be presented as a complete API description.

---

## 13. Related documents

- [API_CONTRACT.md](./API_CONTRACT.md) — response envelopes, errors, and status codes
- [CONTENT_API.md](./CONTENT_API.md) — content, search, drafts, locks, and publish lifecycle
- [BACKEND.md](./BACKEND.md) — route/controller/application boundaries
- [FRONTEND.md](./FRONTEND.md) — API client, session flow, and UI architecture
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md) — stable admin URL contract
- [CORE_HARDENING.md](./CORE_HARDENING.md) — security invariants
- [ITERATION_74.md](../ITERATION_74.md) — API key and JWT plan
