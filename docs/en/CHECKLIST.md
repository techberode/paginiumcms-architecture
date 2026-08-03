---
title: Security and Release Readiness Checklist
description: Practical implementation, testing, CI, deployment, backup, and conditional Hybrid Engine checks
icon: material/check-decagram
---

# Security and Release Readiness Checklist

> This checklist is a living gate for the **`v2.1.0-beta.*`** release family. It does not replace [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md), [TESTING.md](developer/TESTING.md), or [RELEASE.md](developer/RELEASE.md). An item is complete only with evidence for a concrete SHA.

## 1. Legend

| Symbol/state | Meaning |
|---|---|
| ✅ `PASS` | verified automatically and, where needed, manually |
| 🟡 `PASS_WITH_REVIEW` | a non-blocking anomaly has a disposition, owner, and due date |
| 🔍 `INVESTIGATION_REQUIRED` | ambiguous output, new warning, or unexplained skip |
| ❌ `FAILED` | hard gate or security/recovery blocker |
| ➖ `NOT_APPLICABLE` | capability is not implemented in the tag or does not apply to the profile |

Do not mark an item green by intuition. Every critical item needs a command, test, log, screenshot, checksum, or review record.

## 2. Candidate identity

- [ ] Repository and remote are correct.
- [ ] Working tree is clean or every local change is documented.
- [ ] Commit SHA is known.
- [ ] Tag is annotated and points to the correct SHA.
- [ ] Lockfiles belong to the same commit.
- [ ] Release artifact has SHA-256.
- [ ] GitHub CI run belongs to the same SHA.
- [ ] Version in UI/API/docs/release notes is consistent.

## 3. 21-step gate and manual review

- [ ] PHPUnit: 0 failed, 0 errors.
- [ ] Every skipped test has a reason and category.
- [ ] PHPStan Level 8: 0 errors.
- [ ] Composer Audit advisories are evaluated.
- [ ] All Vitest files/tests pass.
- [ ] Frontend security pack passes.
- [ ] TypeScript: 0 errors.
- [ ] ESLint warning count is within budget and trend is reviewed.
- [ ] MSW has no unhandled request.
- [ ] Production build and API URL verification pass.
- [ ] NPM Audit includes severity counts and disposition.
- [ ] Content diagnose: 0 orphans, 0 unreadable, or an approved recovery plan.
- [ ] Security packs 12–20 pass.
- [ ] Static grep has an unambiguous exit and allowlist report.
- [ ] Complete local log is outside the repository.
- [ ] GitHub CI log is sanitized.
- [ ] Warnings, stack traces, network errors, and timing anomalies were read manually.

## 4. CI logs and secrets

- [ ] Tests do not print passwords, TOTP seeds, QR, provisioning URIs, or OTPs.
- [ ] Redactor covers nested JSON, URLs, Base64, and authorization headers.
- [ ] Raw CI output goes only to `$RUNNER_TEMP`.
- [ ] `tee` is not used for raw output.
- [ ] Only the sanitized log is published.
- [ ] Fail-closed grep/scanner finds no secret patterns.
- [ ] Original test exit code is preserved.
- [ ] Raw log is not uploaded as an artifact.
- [ ] `set -x`, `ACTIONS_STEP_DEBUG`, and runner debug are disabled around secrets.

## 5. Authentication and session

- [ ] Bootstrap credentials are unique and changed after first login.
- [ ] Argon2id and password policy are active.
- [ ] Login regenerates the session ID.
- [ ] Session cookie has secure flags for the profile.
- [ ] Login/reset responses do not enable enumeration.
- [ ] Login lockout/rate limit works.
- [ ] Reset token is hashed, expiring, and single-use.
- [ ] 2FA seed is encrypted at rest.
- [ ] OTP rate and resend limits work.
- [ ] Production response/log contains no debug OTP.

## 6. Authorization, RBAC, and Path ACL

- [ ] Every mutating route has authn + authz or an explicit anonymous exception.
- [ ] USER cannot mutate content/media.
- [ ] EDITOR has only declared permissions.
- [ ] ADMIN cannot perform a SUPER_ADMIN operation.
- [ ] Draft, lock, bulk, trash, restore, and import use the same policy as main CRUD.
- [ ] Path ACL uses a canonical path.
- [ ] Worker/job/API key has its own identity and least scopes.
- [ ] Frontend guard is not the only protection.

## 7. CSRF, CORS, WAF, and proxy

- [ ] Mutating browser request without CSRF → `403`.
- [ ] Exempt prefix has a path boundary.
- [ ] Anonymous POST has rate limit and abuse policy.
- [ ] Production CORS allows only explicit origins.
- [ ] `TRUSTED_PROXIES` contains only real proxy hops.
- [ ] Spoofed forwarding headers are ignored.
- [ ] WAF body scanning has a size limit.
- [ ] Multipart and Code Editor have an explicit scan policy.
- [ ] WAF blocks may be non-JSON and the frontend handles them.

## 8. Storage, content, and media

- [ ] Web docroot does not contain internal storage.
- [ ] Public storage route allows only the media subtree.
- [ ] `data/`, `logs/`, `backups/`, and indexes return `404`.
- [ ] Traversal, encoded traversal, and absolute paths are rejected.
- [ ] Symlink/hardlink archive is rejected.
- [ ] Writes use temp + atomic rename.
- [ ] OCC/revision conflict returns `409` or merge flow.
- [ ] Index is derived and rebuildable from SSOT.
- [ ] SVG/HTML/XML responses have safe headers.
- [ ] Upload limits MIME, size, count, and compression ratio.
- [ ] Content diagnostics are healthy.

## 9. Extensions, themes, and Developer Mode

- [ ] Manifest has a schema version and compatibility.
- [ ] ZIP is checked before extraction.
- [ ] Import enters quarantine/staging.
- [ ] Code Policy blocks forbidden constructs.
- [ ] Code Editor uses allowed roots and containment.
- [ ] Developer unlock has TOTP/token, TTL, and a fail-closed secret.
- [ ] Import does not automatically activate.
- [ ] Activation/deactivation/rollback are audited.
- [ ] Frontend extension documents build/redeploy requirements.
- [ ] Untrusted code is not described as sandboxed.

## 10. Outbound and integrations

- [ ] Admin-configured URL passes OutboundUrlGuard.
- [ ] Private/loopback/link-local/metadata IPs are blocked according to profile.
- [ ] Redirect is revalidated.
- [ ] Timeout, response-size, and content-type limits are set.
- [ ] OAuth state/provider/redirect are bound and timing-safe.
- [ ] SMTP/ntfy/webhook/Git tokens are redacted.
- [ ] Provider secret is not in a URL or frontend bundle.
- [ ] Proxy and DNS behavior are tested.

## 11. Logging, audit, and monitoring

- [ ] CR/LF/ANSI log injection is sanitized.
- [ ] CSV export prevents formula injection.
- [ ] Secrets, cookies, and authorization headers are redacted.
- [ ] Request/job correlation ID is available.
- [ ] `401/404` noise is not confused with server errors.
- [ ] Security events use the correct severity.
- [ ] Log delete/archive requires permission and audit.
- [ ] Monitoring covers 5xx, auth, WAF, jobs, disk, backup, and upstream.
- [ ] Time/NTP and UTC policy are consistent.

## 12. Backup, restore, and recovery

- [ ] Backup is outside the web root.
- [ ] Backup is outside the primary failure domain or has an off-host copy.
- [ ] It contains required SSOT and inventory.
- [ ] `APP_KEY` recovery is secured separately.
- [ ] Restore was executed on an isolated profile.
- [ ] Login, 2FA, public content, and index rebuild pass after restore.
- [ ] RTO/RPO are recorded.
- [ ] Code rollback does not overwrite newer content.
- [ ] Recovery handles disk-full, corrupt file, and missing key.
- [ ] Last restore drill is within internal policy.

## 13. Deployment and nginx

- [ ] `docker compose config --quiet` passes.
- [ ] `nginx -t` passes on active configuration.
- [ ] Static and API responses have security headers.
- [ ] `/.well-known/security.txt` is available as text.
- [ ] `expose_php=Off`.
- [ ] Production and demo have separate storage, secrets, ports, and project name.
- [ ] Docker services use `restart: unless-stopped` according to profile.
- [ ] Cron/worker uses the correct identity and `flock`.
- [ ] Backup was created before deployment.
- [ ] Health, login, authz, public content, and changed feature pass smoke tests.
- [ ] Rollback/roll-forward commands are ready.

## 14. Public and anonymous endpoints

- [ ] Route inventory is generated from the concrete tag.
- [ ] Public settings contain no secrets.
- [ ] Demo mode fails closed in production.
- [ ] Login/register/reset/contact/comments/newsletter have abuse controls.
- [ ] Public content returns only published items.
- [ ] Debug endpoint is no-op or disabled outside development.
- [ ] Storage media delivery respects ACL and MIME policy.

## 15. Hybrid Engine — conditional gates

Mark an unimplemented capability `NOT_APPLICABLE`.

- [ ] Redis capability probe and safe fallback.
- [ ] Cache/index are never SSOT.
- [ ] Git publish job is idempotent and uses scoped credentials.
- [ ] S3 driver has scoped bucket policy and recovery reconciliation.
- [ ] API key is secret-at-create-only, revocable, and scoped.
- [ ] JWT is short-lived and not a replacement for admin session/CSRF.
- [ ] Translation creates a draft/diff; Apply is authorized.
- [ ] AI tools are allow-listed and schema-constrained.
- [ ] AI Apply rechecks user permission.
- [ ] AI has no shell, generic filesystem, or autonomous publish.

## 16. Release decision

- [ ] All `FAILED` states are closed.
- [ ] Security `INVESTIGATION_REQUIRED` has a fix or approved disposition.
- [ ] `PASS_WITH_REVIEW` has an owner and expiry.
- [ ] Release evidence bundle includes gate summary, safe log, checksums, and smoke results.
- [ ] Changelog and release notes disclose known issues accurately.
- [ ] Security fixes have regression tests.
- [ ] Incidents are ready to be linked in `ISSUES.md`.
- [ ] Release decision record is approved by a maintainer.

## 17. Related documents

- [Security Policy](../../SECURITY.md)
- [Audit report](../../AUDIT_REPORT.md)
- [Recommendations](../../RECOMMENDATIONS.md)
- [Security Review Guide](SECURITY_REVIEW.md)
- [Testing](developer/TESTING.md)
- [Release lifecycle](developer/RELEASE.md)
- [Deployment](deploy/DEPLOY.md)
- [Incident register](ISSUES.md)
