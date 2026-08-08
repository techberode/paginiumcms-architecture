---
title: Development Security Architecture
description: Threat model, trust boundaries, middleware, secrets, extensions, supply chain, and security gates for PaginiumCMS
icon: material/shield-lock
---

# Development Security Architecture

## 0. Current implementation snapshot — Beta 1

This section names concrete controls confirmed by the updated implementation state. The later chapters remain the broader security contract and target architecture.

| Boundary | Current mechanism |
|---|---|
| Session and login | HttpOnly session, SameSite, lockout, password policy, 2FA/TOTP |
| Authorization | `RoleMiddleware`, `PermissionMiddleware`, `PermissionCatalog`, Path ACL |
| CSRF | synchronizer token, `X-CSRF-TOKEN`, `hash_equals`, narrowly defined exemptions |
| Secrets at rest | `EncryptionService`, 32-byte `APP_KEY`, libsodium or AES-256-GCM |
| Public storage | allow-list for media prefixes only; data/logs/backups/dev/cache are denied |
| Outbound/SSRF | `OutboundUrlGuard`, production HTTPS, DNS/IP checks, private/reserved range blocking |
| WAF and abuse | scenario-based WAF, bounded JSON body scan, global and dedicated login/OTP limits |
| Extensions | Zip-Slip protection, manifest/policy scan, controlled extension directory, write-time `validateUntrusted` |
| Logs and exports | `LogSanitizer`, CSV-injection protection, separate security and audit concerns |
| CI output | sanitized PHPUnit output and a fail-closed redaction gate under ISS-120 |

### CI log hygiene — ISS-120

GitHub Actions must never publish raw backend test output. The implementation contract is:

```text
run-backend-tests-ci.sh
→ raw output only in runner temp
→ sanitize-ci-log.py
→ verify-ci-log-redaction.sh
→ only then GitHub console/artifact
```

The complete local log remains outside the repository. The public workflow template is [`../../LOCAL_TEST_LOGS.md.example`](../../../LOCAL_TEST_LOGS.md.example); raw logs, the local checklist, and sanitized working copies are not committed.

### Dependency disclosure

React Router advisories published after `v2.1.0-beta.2` were addressed in `v2.1.0-beta.3`. An audit exit code must not be interpreted without checking the severity threshold and complete output; a finding below the configured threshold may still require `PASS_WITH_REVIEW` or `INVESTIGATION_REQUIRED`.

> This document defines security invariants for the **`v2.1.0-beta.*`** release family and the target Hybrid Headless Content Engine. Concrete implementation must always be verified against the code and tests in the relevant tag. Public vulnerability reporting is governed by the root [SECURITY.md](../../../SECURITY.md).

## 1. Security principles

1. **Flat-file SSOT does not mean a trusted filesystem.** Every path, filename, archive, and metadata object is validated.
2. **The backend decides.** A frontend guard, hidden button, or route redirect is not authorization.
3. **Fail closed at a security boundary.** Unknown permissions, invalid revisions, unverified providers, and unreadable manifests are not interpreted optimistically.
4. **Least privilege.** Users, workers, API keys, plugins, and AI tools receive only the required scope.
5. **Authoritative and derived layers stay separate.** Cache, index, Git remote, and AI output must never silently overwrite SSOT.
6. **Secrets are not content.** They do not belong in URLs, logs, repositories, frontend bundles, or prompts without an explicit need.
7. **The security gate is layered.** Static scanning, runtime controls, configuration, audit, and operational monitoring complement each other.
8. **Recovery is a security function.** A backup without a restore test and a rollback without key preservation are not reliable protection.

## 2. Scope and assumptions

The current baseline assumes one CMS instance per deployment. Full multi-tenant isolation is not an implicit Public Beta property. If one process serves multiple customers or mutually untrusted teams, it needs a dedicated tenant model, namespacing, keys, quotas, audit, and isolation tests.

This document covers:

- the public web and public API,
- the staff/admin SPA,
- flat-file content and settings,
- uploads and media delivery,
- plugins, themes, Code Editor, and Developer Mode,
- external OAuth/SMTP/ntfy/S3/Git/translation/AI providers,
- scheduler, workers, and asynchronous jobs,
- build, CI, release, and deployment supply chain.

## 3. Protected assets

| Asset | Compromise impact | Primary controls |
|---|---|---|
| user accounts and sessions | account takeover, escalation | passwords, 2FA, session policy, rate limit, audit |
| `APP_KEY` and encrypted secrets | decryption of provider credentials | file permissions, secret management, backup separation |
| content SSOT | defacement, loss, supply-chain publication | RBAC/ACL, revision, atomic write, versions, backup |
| media | malware, XSS, private-asset leak | upload policy, content disposition, ACL, storage driver |
| settings | SSRF, mail abuse, auth bypass | schema, secret encryption, permission, capability probe |
| extension code | RCE, exfiltration, persistence | staged import, CodePolicy, allow-list, isolation limits |
| audit and logs | evidence destruction, log injection | append policy, sanitization, access control, export integrity |
| Git/release artifacts | malicious update | immutable refs, checksum, review, CI, optional signing/SBOM |
| queue/jobs | confused deputy, duplicate action | actor identity, scope, idempotency, retry/dead-letter audit |
| AI/translation context | prompt injection, data leak, unauthorized Apply | minimal context, tool schema, authorization, human review |

## 4. Actors and threats

| Actor | Typical objective | Required defense |
|---|---|---|
| anonymous client | abuse forms, scanning, XSS payload | rate limit, WAF, validation, output encoding |
| registered USER | obtain editor/admin capability | RBAC, object/path authorization |
| EDITOR | access outside an allowed tree | permission + Path ACL + audit |
| ADMIN | accidentally configure a risky target | schema, SSRF guard, capability test, warnings |
| SUPER_ADMIN | legitimate high-impact mutation or compromised account | 2FA, re-auth, audit, recovery, minimal account count |
| malicious plugin/archive | RCE, persistence, exfiltration | import quarantine, scanner, policy, runtime boundary |
| compromised provider | malicious response or redirect | timeout, TLS, response limits, schema, redirect revalidation |
| network attacker | hijack or downgrade | HTTPS, Secure cookies, proxy trust, HSTS rollout |
| host file reader | steal content or secrets | Unix permissions, secret encryption, host hardening |
| compromised CI account | inject a malicious release | branch protection, least privilege, immutable artifact evidence |
| prompt injection in content | force an AI tool action | content/instruction separation, tool allow-list, Apply authorization |

## 5. Trust boundaries and data flows

Simplified mutation flow:

```text
client
→ reverse proxy/TLS
→ security headers + request limits
→ WAF/rate limit
→ session/API authentication
→ CSRF (session mutations)
→ RBAC/permission/Path ACL/2FA
→ payload + path + revision validation
→ atomic SSOT write
→ version/audit/event
→ index/cache invalidation
→ optional job (Git, translation, AI, provider)
```

Every arrow is a boundary. A job started after a successful save must not retroactively change the fact that the local write succeeded; it needs its own state and retry model.

## 6. HTTP and middleware pipeline

Slim middleware order depends on LIFO execution and must be protected by an integration test. The documented logical order is:

1. trusted proxy and canonical request metadata,
2. request ID and safe log context,
3. security headers/CORS,
4. body/size/content-type limits,
5. firewall/WAF,
6. global and route-specific rate limit,
7. maintenance policy,
8. session or API authentication,
9. CSRF for session mutations,
10. role/permission/Path ACL/2FA,
11. controller/application service,
12. error normalization and audit.

Important rules:

- WAF may stop a request before the JSON responder; clients must handle text or empty `403` responses.
- CORS is not authorization. `Access-Control-Allow-Origin` does not protect an endpoint from a server-to-server client.
- `APP_ENV=testing` exceptions must not be activatable in production through a request header.
- Forwarded headers are trusted only from the `TRUSTED_PROXIES` allow-list.
- Body scanning has explicit limits and bypasses only precisely defined multipart/code-editor flows, not a wildcard based on URL naming.

## 7. Authentication and sessions

### 7.1 Passwords and login

- use modern `password_hash`/`password_verify` according to supported PHP,
- regenerate the session ID after successful login,
- return generic failures without account enumeration,
- combine IP and identity rate limits with a safe fallback,
- avoid a lockout design that enables cheap permanent denial of service against another account,
- sensitive changes may require password re-confirmation or 2FA.

### 7.2 Session cookie

A production profile should set:

- `HttpOnly`,
- `Secure` under HTTPS,
- `SameSite=Lax` or stricter according to SSO topology,
- bounded lifetime and idle timeout,
- server-side invalidation on logout/password reset/role downgrade,
- an appropriate cookie path/domain.

Session IDs, cookies, and CSRF tokens are not written to normal logs.

### 7.3 2FA and OTP

- secrets are encrypted at rest,
- recovery workflow is audited,
- OTP has replay and rate-limit protection,
- debug OTP is never returned in production,
- SUPER_ADMIN/staff policy is applied consistently to UI and API,
- role downgrade or account deactivation invalidates relevant sessions.

### 7.4 SSO

OAuth/OIDC integration must verify:

- the exact redirect-URI contract,
- random session-bound `state` using timing-safe comparison,
- issuer/audience/nonce as required by the protocol,
- TLS, timeout, and response-size limits,
- JIT role no higher than configured and never implicit SUPER_ADMIN,
- identity mapping without email/provider-subject collisions.

## 8. API authentication It.74

API keys and short-lived JWT are **implemented** as an additive headless layer; they do not replace the admin session model.

| Surface | Auth | Notes |
|---------|------|-------|
| Admin SPA | Session + CSRF + RBAC + 2FA | unchanged |
| `/api/headless/*` | Bearer API key or short JWT | CSRF-exempt; route allow-list via `ApiScopePolicy` |
| `/api/admin/platform/api-keys` | Session + `api-keys:manage` | create/list/revoke/rotate/audit; copy-once secret |

Required properties (enforced):

- key secret is displayed only at creation/rotation and stored as HMAC verifier (`API_KEY_PEPPER`),
- identifier, scopes, owner, created/last-used/expires/revoked metadata in `data/api-keys.json`,
- rotation revokes the old key atomically; audit events in `SecurityAuditStore`,
- no key in a query string,
- JWT: separate `API_JWT_KEY`, HS256 only, max TTL 900s, mandatory `iss`/`aud`/`sub`/`jti`/`iat`/`nbf`/`exp`/`scope`, optional `jti` deny-list,
- scope cannot exceed key allowance; JWT issue via `token:issue` is subset-only,
- invalid managed Bearer on any route returns `401` without session fallback,
- logs and audit record key ID / event type, never secret material.

CSRF does not apply to `/api/headless` bearer mutations. Do not store long-lived tokens in browser localStorage.

## 9. Authorization, RBAC, and Path ACL

Authorization is evaluated for every protected operation and the concrete target object/path.

Order:

```text
authenticated actor
→ account active/session valid
→ role/permission
→ Path ACL or object policy
→ 2FA/re-auth condition
→ revision/lock
→ mutation
```

- SUPER_ADMIN bypass must be explicit and audited.
- A user cannot assign a role higher than they are allowed to manage.
- Batch operations validate every item or fail transactionally according to the contract.
- Search/list endpoints must not expose objects hidden by the detail endpoint.
- Signed media URLs must not bypass Path ACL.
- Workers/jobs inherit a minimal identity snapshot or technical scope, not implicit root.

User guide: [ACCESS_CONTROL.md](../user/ACCESS_CONTROL.md).

## 10. CSRF and the browser boundary

Session mutations use a synchronizer token:

1. server creates a token bound to the session,
2. SPA sends it in a header,
3. middleware uses timing-safe comparison,
4. session invalidation invalidates the token,
5. the client may perform at most a controlled refresh/retry.

Exempt routes must be an exact allow-list. A public form without session CSRF needs alternative controls: origin/content-type policy, rate limit, anti-automation, input validation, and abuse monitoring.

`SameSite` is a supplement, not the sole CSRF defense.

## 11. Validation, encoding, and safe rendering

- validate input by type, range, enum, format, and domain rule,
- canonicalize paths before allow/deny decisions,
- encode output for HTML/attribute/URL/JSON context,
- sanitize Markdown/HTML preview through an allow-list,
- explicitly allow URL schemes and reject `javascript:`-style variants,
- deliver SVG/HTML/XML uploads according to a safe policy, commonly attachment + sandbox CSP,
- error responses contain no internal paths, stack traces, or secrets.

A WAF pattern is not a replacement for validation and output encoding.

## 12. Flat-file storage and public delivery

Authoritative data should remain outside the web root. The public storage controller allows only explicit media prefixes and validates:

- normalized relative path,
- prefix/registry record,
- file existence and type,
- permission/private flag,
- safe `Content-Type`, `Content-Disposition`, and CSP,
- range/cache behavior according to the media contract.

Direct public paths to users, settings, logs, backups, cache, developer tokens, and internal metadata are forbidden.

Writes use a temporary file, validation, and atomic rename. Lock/revision protects logical consistency; filesystem permissions protect the host layer.

Details: [STORAGE.md](../architecture/STORAGE.md).

## 13. Secrets and encryption at rest

`APP_KEY` must have the required entropy and length. It supports application-level encryption of sensitive fields such as 2FA secrets and provider credentials.

Rules:

- `.env` is not in the repository or release archive,
- a stored secret is not returned to the UI,
- an empty masked field does not overwrite an existing secret,
- key rotation uses a versioned ciphertext format and migration plan,
- a data backup without the corresponding key may not be restorable,
- storing the key beside the data weakens protection; keep it separately,
- logs, audit, exception context, and telemetry apply redaction.

Encryption at rest does not protect against a compromised running process that has the key. Host hardening and least privilege remain necessary.

## 14. Outbound communication and SSRF

Every admin-configurable URL for OAuth, webhooks, ntfy, S3-compatible endpoints, Git, translation, or AI providers passes through a shared outbound guard.

Required checks:

- allowed schemes, usually HTTPS in production,
- parsed and canonical host,
- DNS resolution of all results,
- rejection of private, loopback, link-local, multicast, and reserved ranges,
- revalidation of every redirect,
- timeout, connect timeout, redirect count, and response-size limit,
- no URL userinfo,
- safe proxy policy,
- provider-test audit without secrets.

DNS may change between validation and connection. Prefer an HTTP-client/network layer that can bind validation to the actual target IP or revalidate on connect/redirect. A one-time URL regex is not SSRF protection.

## 15. Uploads, archives, and media

Upload pipeline:

```text
request/body limit
→ extension + MIME + content sniff
→ temporary quarantine
→ decoder/scanner/policy
→ safe name and metadata
→ atomic move or storage-driver commit
→ registry/audit
```

For ZIP/import:

- limit entry count, compressed and expanded size, and ratio,
- reject absolute paths, `..`, NUL, and non-canonical names,
- reject symlink/hardlink entries according to policy,
- never extract directly into an active runtime directory,
- validate manifest/schema and code policy before registration,
- imported extensions remain disabled,
- rollback removes only owned staged artifacts.

Antivirus is supplemental. A negative ClamAV result does not make SVG or PHP code safe for inline execution.

## 16. Plugins, themes, and Code Editor

`CodePolicyEngine::validateUntrusted` is a write/import gate for untrusted extension/theme/layout trees. It rejects or restricts high-risk constructs according to [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

Important boundaries:

- the scanner is not a complete PHP sandbox,
- `include`, `require`, dynamic callables, and autoload can create an execution surface,
- backend plugins run in the application process unless separated by a process/container boundary,
- filesystem jails require canonical paths and symlink-race controls,
- Code Editor save does not mean registration, activation, frontend build, Git push, or deployment,
- Vite `import.meta.glob` is a build-time mechanism,
- Developer Mode uses a short unlock, 2FA/developer secret, audit, and a production fail-closed profile.

For an untrusted community ecosystem, the target should be stronger process/container isolation or a declarative extension model with constrained capability APIs.

## 17. Firewall, rate limiting, and abuse protection

The application WAF is an additional layer. It does not replace reverse-proxy limits, a host firewall, safe routing, or validation.

- rules have scenarios, severity, and false-positive tests,
- incident logs sanitize inputs,
- whitelists are narrow, audited, and optionally time-limited,
- the limiter derives client IP through trusted-proxy rules,
- login/OTP/public forms use separate budgets,
- multipart and code-editor body-scan exemptions are minimal,
- an in-memory/file limiter may not be globally consistent on multi-node deployments; a distributed backend is a capability, not an automatic assumption.

## 18. Logging, audit, and monitoring

Distinguish:

| Stream | Purpose |
|---|---|
| access/request log | operational request trail |
| application log | errors and diagnostics |
| security event | WAF, lockout, policy denial, suspicious provider |
| domain audit | who changed content, settings, users, or permissions |

Required properties:

- UTC timestamp and request/job correlation ID,
- actor ID/role and target, not credentials,
- CR/LF/ANSI sanitization,
- safe CSV export against formula injection,
- redaction of secrets and sensitive payloads,
- permissions and retention,
- atomic rotation without damaging the active writer,
- alerts for repeated authorization denial, provider failure, queue backlog, and integrity issues.

Audit should be harder to alter than a normal application log. Public Beta need not provide a cryptographic append-only ledger, but documentation must not call editable JSON irreversible evidence.

## 19. Dependency and release supply chain

The release gate includes:

- Composer/npm lockfile installation,
- SCA audit with a versioned severity policy,
- secret scanning,
- review of GitHub Actions permissions,
- pinning or review of third-party actions,
- immutable release commit/tag,
- artifact checksum,
- archive-content inspection,
- separation of build and deploy credentials,
- ideally SBOM and provenance/signing.

An advisory must not disappear merely because CI blocks at `high` while the finding is `moderate`. Temporary acceptance belongs in `ISSUES.md` with an owner, reason, and review date.

A pull request from a fork must not receive production secrets. Deployment environments require explicit protection and minimal token scopes.

## 20. Scheduler, workers, and queue

An asynchronous job is a separate security-principal boundary.

Job payloads contain only required identifiers, not plaintext secrets or an entire session object. At execution time:

- load the current target/revision,
- validate job type and schema,
- apply service capability and, according to contract, actor permission,
- use an idempotency key,
- bound retries/backoff,
- send terminal failure to a visible failure/dead-letter state,
- audit enqueue and final result,
- never allow arbitrary class/method deserialization.

A queue worker is not SUPER_ADMIN merely because it runs through CLI.

## 21. Git publishing

Git is a distribution/publishing layer, not a replacement for local SSOT.

- repository URL and credentials are secret/outbound surfaces,
- branch/ref has an allow-list and safe naming,
- commit content comes from a validated snapshot/revision,
- shell commands are not built by concatenating user input,
- push failure does not turn `stored` into false,
- retry does not create duplicate commits,
- audit distinguishes commit creation from remote push,
- checkout/worktree is isolated from active content storage.

A webhook or remote sync back into CMS requires signature verification, replay protection, and a conflict policy.

## 22. Localization, translation, and AI

### 22.1 Translation

- the provider receives only required content and metadata,
- secrets/PII are not sent without authorization and a documented policy,
- responses have size/schema limits,
- output is a proposal/draft,
- Diff is bound to the source revision,
- Apply rechecks actor permission and revision,
- publication is a separate human or explicitly authorized operation.

### 22.2 AI agent

A system prompt is not a security boundary. Protection comes from:

- separating system instructions from untrusted content,
- minimal authorized context retrieval,
- allow-listed tools with JSON schemas,
- no shell or generic filesystem tool,
- per-tool permission checks,
- preview/diff before mutation,
- fresh authorization at Apply,
- limits for tokens, time, cost, and outbound data,
- audit of requests, tools, and results without sensitive prompt dumps.

Autonomous publishing and an “AI superuser” are outside the safe baseline scope.

## 23. Backup, restore, and incident recovery

Backups should cover authoritative content, users, settings metadata, media metadata/objects according to the driver, and required versions. Keys and credentials need their own secure recovery path.

Test regularly:

- restore into an empty separate root,
- archive validation against traversal/symlinks,
- schema/migration compatibility,
- index/cache rebuild,
- preservation or restoration of `APP_KEY`,
- rotation of compromised secrets,
- service recovery without enabling mutations before an integrity check.

Minimum incident response:

1. restrict mutations/enable maintenance,
2. preserve logs and relevant artifacts,
3. identify commit, release, and scope,
4. rotate affected credentials,
5. fix and add a regression test,
6. restore from a verified source,
7. document the issue/advisory according to disclosure policy.

## 24. Security gates and release blockers

Release blockers include:

- authentication or authorization bypass,
- CSRF bypass on a session mutation,
- traversal/Zip-Slip/symlink escape from a jail,
- web access to users/settings/logs/backups,
- corruption or silent overwrite of content SSOT,
- secret leakage into logs, bundles, or artifacts,
- an unpatched exploitable dependency according to the approved policy,
- extension import leading to uncontrolled code,
- unauthorized AI/tool Apply or publication,
- unreproducible upgrade/rollback for a release-impacting change.

Security tests and quality gates: [TESTING.md](TESTING.md).

## 25. Reporting and public incident log

Do not first disclose a sensitive vulnerability in a public issue. Follow the root [SECURITY.md](../../../SECURITY.md).

`docs/ISSUES.md` is the public technical log for repaired or safely disclosed problems. During its bilingual processing in Iteration 13:

- each issue number in the overview will be a clickable link,
- each detail will use a stable explicit anchor such as `<a id="iss-078"></a>`,
- details will include symptom, cause, impact, fix, and verification,
- commit, changelog, and release links will be added when available.

A private `SECURITY_ISSUES.md` may contain a detailed internal incident workflow, but it must remain uncommitted and contain no plaintext secrets.

## 26. Operations checklist

- [ ] HTTPS and correct trusted-proxy configuration,
- [ ] `APP_ENV=production`, debug disabled,
- [ ] public docroot limited to `backend/public/`,
- [ ] staff 2FA according to policy,
- [ ] real `APP_KEY` and protected recovery procedure,
- [ ] CSRF/RBAC/Path ACL regression tests green,
- [ ] WAF/rate limit and log sanitization verified,
- [ ] storage, logs, backups, and developer data inaccessible from the web,
- [ ] Composer/npm advisory review documented,
- [ ] backup restore test completed,
- [ ] worker/cron runs with least privilege,
- [ ] outbound providers passed SSRF/capability tests,
- [ ] release artifact has a checksum and contains no secrets,
- [ ] incident/disclosure contact works.

## 27. Related documents

- [Root security policy](../../../SECURITY.md)
- [Security review](../SECURITY_REVIEW.md)
- [Testing and quality gates](TESTING.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Storage](../architecture/STORAGE.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Access control](../user/ACCESS_CONTROL.md)
- [Firewall](../user/FIREWALL.md)
- [Logging](../user/LOGGING.md)
- [Extension Code Policy](EXTENSION_CODE_POLICY.md)
- [Beta infrastructure](BETA_INFRA.md)
- [Issues](../ISSUES.md)
