---
title: API Response Contract
description: Unified JSON envelopes, errors, and client compatibility
icon: material/code-json
---

# 📦 API Response Contract

> **Status:** canonical contract for Public Beta checkpoint `v2.1.0-beta.23`  
> **Applies to:** Slim backend, React API client, MSW fixtures, and headless integrations  
> **Exceptions:** binary/streamed responses, RSS/sitemap/robots, and pre-routing WAF blocks

The contract lets a client reliably distinguish success, validation failure, conflict, missing identity, and follow-up state without parsing a human sentence. It also acknowledges the legacy auth envelope and plain WAF 403 rather than pretending they have already been migrated.

---

## 1. General rules

A JSON endpoint returns:

```http
Content-Type: application/json; charset=utf-8
```

Core rules:

- `success` is a boolean and is present in every standard JSON envelope,
- successful payload belongs in `data`, except documented legacy auth responses,
- failure includes a safe human message in `error`,
- field-level validation uses `errors`,
- 409 uses typed detail in `conflict` or `lock`,
- a production response never contains a stack trace, filesystem path, secret, token, or raw provider response,
- machine field names remain stable and non-localized; the UI message may be localized, not the contract key.

The recommended target is to add machine-readable `code` and correlation `requestId`. Until they are implemented consistently on every error path, clients must treat them as optional.

---

## 2. Standard success

```json
{
  "success": true,
  "data": {
    "id": "page-home"
  },
  "message": "Content was saved"
}
```

| Field | Type | Required | Rule |
|-------|------|----------|------|
| `success` | boolean | yes | always `true` |
| `data` | any JSON type | yes in the standard envelope | object, array, scalar, or `null` as documented |
| `message` | string | no | UX aid; clients must not derive state from it |
| `meta` | object | no | pagination, capability, or safe transport metadata |

`204 No Content` is valid for an endpoint that explicitly documents an empty response, such as disabled telemetry. It has no JSON body and must not be passed to a JSON parser.

---

## 3. Paginated success

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 142,
    "total_pages": 8
  }
}
```

Canonical fields are `page`, `per_page`, `total`, and `total_pages`. Domain aggregates such as `tags` or `total_published` may be included in `meta` but must not change the meaning of the core fields.

A legacy list without `page`/`per_page` may return the full array without `meta`. New clients should always send explicit pagination. Cursor pagination is not part of the current contract.

---

## 4. Standard error

```json
{
  "success": false,
  "error": "The requested operation could not be completed",
  "code": "operation_failed",
  "requestId": "req_..."
}
```

The guaranteed minimum in the current contract is `success: false` and `error`. `code` and `requestId` are target optional fields until contract tests cover them on every error path.

`error` must be safe for users. Incident detail belongs in server logs correlated by request ID, not in the browser response.

---

## 5. Validation error 422

```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "email": ["Invalid email format"],
    "content.title": ["Title is required"]
  }
}
```

`errors` is `Record<string, string[]>`. A field path must be stable and map to a form. The backend remains authoritative even when the frontend uses the same schema for immediate UX validation.

Sensitive input such as a password, API key secret, or provider token must not be echoed in `errors` or logs.

---

## 6. Content conflict 409

```json
{
  "success": false,
  "error": "Content changed on the server",
  "conflict": {
    "serverRevision": "<revision>",
    "serverContent": {},
    "changedAt": "2026-08-02T12:00:00+02:00"
  }
}
```

`serverRevision` is a concurrency fingerprint, not cryptographic proof of integrity. `serverContent` is returned only within the user's read permissions; a conflict must not leak another locale, draft, or ACL-protected field.

After merging, the client saves against the new server revision. Force overwrite is a separate privileged operation, not a hidden retry.

---

## 7. Lock conflict 409

```json
{
  "success": false,
  "error": "Another user is editing this content",
  "lock": {
    "contentType": "page",
    "slug": "about",
    "ownerId": "user-123",
    "expiresAt": "2026-08-02T12:05:00+02:00"
  }
}
```

Lock details must not expose more personal information than the UI requires. Server timestamps should use a single ISO 8601 format; historical epoch timestamps must be normalized during migration or clearly typed.

---

## 8. Legacy auth envelope

Existing login/register/2FA flows may return root-level fields:

```json
{
  "success": true,
  "user": {
    "id": "user-123",
    "email": "admin@example.com",
    "roles": ["ADMIN"]
  },
  "requires_two_factor": false
}
```

This is a **documented compatibility exception**, not a template for new endpoints. The frontend parser may temporarily normalize:

```text
legacy root fields → internal data model
```

Migration must coordinate backend, frontend, MSW, and contract tests. Silently moving `user` into `data` without versioning would break the current client.

---

## 9. OTP challenge envelope

A sensitive mutation may return HTTP 200 with a challenge instead of a completed operation:

```json
{
  "success": true,
  "requires_otp": true,
  "challenge_id": "otp_abc123",
  "message": "OTP verification is required"
}
```

Semantics:

- the request was accepted but the domain action is not yet confirmed,
- the frontend opens the OTP flow and does not report save/publish as complete,
- the challenge has an owner, TTL, attempt/resend limit, and binding to one action,
- `debug_code` is allowed only in explicit development/testing mode and never in production.

A future typed `data.actionState` may be cleaner, but the compatible wire format must not change without a migration plan.

---

## 10. Asynchronous and derived follow-up

The Hybrid Engine must distinguish primary storage from publishing/translation/AI jobs. Recommended domain payload:

```json
{
  "success": true,
  "data": {
    "resource": { "type": "article", "slug": "news", "revision": "<revision>" },
    "storageState": "stored",
    "publishState": "pending_publish",
    "jobId": "job_..."
  }
}
```

States such as `stored`, `pending_publish`, `committed`, `pushed`, and `publish_failed` belong in `data`, not in the boolean `success`. A failed Git push after a successful local save must not claim that content was not stored.

The exact field model is finalized during It.70 implementation and must be shared by backend, frontend, and event payloads.

---

## 11. Non-JSON exceptions

| Endpoint/class | Content type | Client rule |
|----------------|--------------|-------------|
| RSS/sitemap | XML | do not parse through `ApiResponse` |
| `robots.txt` | text/plain | text response |
| media/download/export | file-specific | stream/blob; validate filename/content disposition |
| `204` telemetry/no-op | no body | do not invoke JSON parser |
| WAF jail/tarpit | often empty or text 403 | process status before content type |

A pre-routing WAF may intentionally return plain 403 because it rejects the request before the route responder. Clients must handle non-JSON 401/403/5xx without a secondary parser failure.

---

## 12. HTTP status codes

| Code | Meaning in PaginiumCMS |
|------|------------------------|
| `200` | successful read/mutation or OTP challenge in the legacy flow |
| `201` | resource created |
| `202` | reserved for an accepted async job when a route explicitly introduces it |
| `204` | success without a body |
| `400` | malformed request or failed basic precondition |
| `401` | missing/invalid/expired identity |
| `403` | identity exists but lacks permission/scope, or pre-routing WAF block |
| `404` | resource does not exist or is masked by security policy |
| `409` | revision/lock/idempotency conflict |
| `412` | planned use for a failed HTTP precondition if `If-Match` is adopted |
| `413` | request/upload too large |
| `415` | unsupported content type/MIME |
| `422` | schema/field validation |
| `429` | rate limit; do not expose sensitive limiter internals |
| `500` | unexpected server error; safe message + server log |
| `503` | maintenance or temporarily unavailable required capability |

An unavailable optional Redis/S3/provider must not automatically cause a site-wide 503 when a safe Classic fallback exists.

---

## 13. JSON data conventions

- timestamps: ISO 8601/RFC 3339 with offset or `Z`,
- booleans: real JSON booleans, not `"0"`/`"1"`,
- absent and `null` have different meanings and must be documented,
- enums use stable lowercase ASCII values,
- IDs and slugs are not translated,
- locale uses a validated BCP 47-like tag from an implementation allow-list,
- `content` may be Markdown, HTML, or serialized Tiptap JSON according to `contentFormat`,
- a secret field is write-only/redacted; placeholder `********` must never be saved as the new secret.

---

## 14. Cache and conditional requests

It.69 plans `ETag`/`Last-Modified` and `304 Not Modified`. A 304 has no JSON body. Cache keys must include every factor that changes a representation, especially public/admin scope, locale, query filters, and permission-relevant variants.

A private/admin response must never enter a public cache. `Vary` and cache-control policy require contract tests rather than relying on an undocumented nginx setting.

---

## 15. Frontend parser rules

The centralized API client must:

1. inspect status and content type first,
2. safely handle empty/non-JSON bodies,
3. normalize only documented legacy auth responses,
4. preserve field errors for forms,
5. distinguish 401, 403, 409, 422, 429, and maintenance,
6. not replace the server `error` with a generic JSON parse error,
7. redact tokens and sensitive payloads from client telemetry,
8. never automatically retry non-idempotent writes without revision/idempotency protection.

---

## 16. Contract testing and versioning

Required fixtures/tests:

- standard success and paginated success,
- standard/validation error,
- content conflict and lock conflict,
- legacy auth success + 2FA,
- OTP challenge,
- plain WAF 403 and 204 without a body,
- binary/export response,
- 429 and maintenance,
- planned stored/publish follow-up,
- production error without stack traces or secrets.

A breaking envelope change requires a changelog, backend test, frontend parser test, MSW fixture update, and a migration period or API versioning.

---

## 17. Related documents

- [API.md](./API.md) — endpoint families and authentication matrix
- [CONTENT_API.md](./CONTENT_API.md) — resource model and OCC lifecycle
- [FRONTEND.md](./FRONTEND.md) — centralized client and error UX mapping
- [CORE_HARDENING.md](./CORE_HARDENING.md) — WAF, CSRF, CORS, and redaction
- [VERSIONING.md](./VERSIONING.md) — revisions, locks, merge, and publish state
