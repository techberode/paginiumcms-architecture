# Iteration 74 — API keys and short-lived JWT

> **Status:** ✅ Complete in `[Unreleased]` (Phases 74a + 74b)  
> **Priority:** 🟡  
> **Wave:** [Hybrid Engine HE-5](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.68](ITERATION_68.md) · cached lookup from [It.69](ITERATION_69.md) is recommended

## Confirmed decision

| Client | Authentication |
|--------|----------------|
| Admin SPA | existing PHP session + CSRF + RBAC + optional 2FA remains unchanged |
| Server integration/CI | scope-limited API key |
| Short delegated task | optional short-lived JWT issued to a trusted client |
| Browser admin token in localStorage | prohibited |

It.74 **does not replace session authentication with JWT**. It expands the headless contract without regressing the human admin flow.

---

## API-key threat and data model

A key has a public identifier and a secret part displayed once, for example:

```text
pgk_<key-id>_<high-entropy-secret>
```

`data/api-keys.json` stores only:

- `id`, label, owner/creator,
- a hash/HMAC of the secret part, never plaintext,
- scopes and an explicit route-policy profile,
- `createdAt`, `expiresAt`, `lastUsedAt`,
- revoke/rotation state,
- an optional IP/egress hint only when safely implemented.

The recommended verifier is HMAC-SHA-256 with a separate `API_KEY_PEPPER`, or an equivalent safe verifier for a random high-entropy token. Comparison uses `hash_equals`. `APP_KEY` should not be reused indiscriminately for every cryptographic purpose.

---

## Phase 74a — read-only API keys

MVP scopes:

- `content:read` — published/headless slice only,
- `media:read` — according to public/private policy,
- `settings:read` — only an explicit public integration slice.

Every route must appear in an allow-list map. A scope string alone does not automatically expose all `/api/admin/*` endpoints.

Backend components:

| Component | Responsibility |
|-----------|----------------|
| `ApiKeyStore` | atomic flat-file SSOT, revoke/rotate, schema validation |
| `ApiKeyVerifier` | prefix parsing, key lookup, constant-time verification |
| `BearerAuthMiddleware` | separate from session resolver; stable 401/403 |
| `ApiScopePolicy` | route + method + required scopes |
| `ApiKeyRateLimitIdentity` | rate limit by key ID without logging the secret |
| Admin UI/API | create, copy once, list metadata, revoke, rotate |

---

## Phase 74b — scoped write and JWT

Write scopes (`content:write`, `media:write`, later `git:publish`) are explicit opt-ins and use the same domain validators as the session flow.

JWT is only for short-lived delegation:

- separate `API_JWT_KEY` or an asymmetric keypair according to deployment policy,
- at least `iss`, `aud`, `sub`, `jti`, `iat`, `nbf`, `exp`, `scope` claims,
- a short maximum TTL,
- no long-lived refresh token in browser storage,
- mandatory audience and issuer validation,
- revocation of critical tokens through short TTL and an optional flat-file `jti` deny-list,
- server-side algorithm allow-list; no `alg=none` or algorithm confusion.

The JWT issuing endpoint is available only to an authorized session or an API-key client with `token:issue` and explicit policy.

---

## CSRF and resolver rules

- session mutation routes remain CSRF-protected,
- Bearer routes do not use cookie session as authority and are CSRF-exempt (`/api/headless/*`),
- a request with an invalid Bearer token must not silently fall back to another auth mechanism,
- the admin SPA continues to use cookies/session; a JWT frontend refactor is a non-goal,
- adding API keys does not automatically widen CORS policy.

---

## Security and operations

- plaintext is shown only during creation/rotation,
- API responses and audit never expose the verifier or secret,
- keys provide expiry, revoke, and rotation workflows,
- `lastUsedAt` must not cause a lock/write on every request; use bounded/coalesced updates or derived metrics,
- rate limit and audit identify the key by ID,
- log sanitization masks tokens in headers and errors,
- backup policy protects the key store; restore must not resurrect revoked keys without an incident procedure,
- creating sensitive write scopes may require recent 2FA.

Production env:

```bash
API_KEY_PEPPER=<long-random-secret>
API_JWT_KEY=<separate-long-random-secret>
```

---

## Frontend

Route `/platform/api-keys` provides:

- creation wizard with label, scopes, expiry, and explanations,
- copy-once secret panel,
- secret-free list: ID prefix, scopes, created/expiry, last used, status,
- revoke/rotate with confirmation and audit,
- `curl` examples without real tokens,
- warning that the key belongs in a secret manager, not Git or browser localStorage.

---

## Out of scope

- removing PHP sessions,
- OAuth/OIDC authorization server,
- anonymous write endpoints,
- wildcard admin scope,
- long-lived browser refresh tokens,
- SQL/Redis as the only key store,
- issuing JWT directly from a password outside the existing login/2FA flow.

---

## Tests

- valid read key → `200`; missing scope → `403`; revoked/expired → `401`,
- plaintext cannot be retrieved a second time,
- constant-time hash/HMAC verification and malformed-prefix handling,
- route allow-list blocks unmarked admin endpoints,
- a write key passes the same schema/RBAC domain policy,
- session mutation still requires CSRF,
- Bearer failure does not fall back to session,
- JWT validates issuer, audience, expiry, nbf, jti, and allowed algorithm,
- token/header redaction in logs,
- concurrent key-store updates and rotation,
- Classic admin login is identical to `beta.23`.

---

## Definition of Done

- [x] An external client reads published content through a scoped API key (`/api/headless/*`).
- [x] The key is stored only as a safe verifier; the secret is copy-once at create.
- [x] Route/method allow-list and per-key rate limit are active.
- [x] Revoke works end to end; list/create via admin API.
- [x] Admin UI `/platform/api-keys` wizard.
- [x] Rotate endpoint + audit export.
- [x] JWT uses a separate key, short TTL, and mandatory claim validation (Phase 74b).
- [x] Session + CSRF + 2FA admin flow has no regression (integration tests).
- [x] SK/EN API and security documentation fully updated.

## Related

[Hybrid Engine security](architecture/HYBRID_ENGINE.md) · [developer security](developer/SECURITY.md)
