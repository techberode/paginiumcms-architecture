---
title: Beta Infrastructure and Release Readiness
description: Clean-clone acceptance, CI gate, security baseline, operations evidence, and rollback for public beta
icon: material/rocket-launch
---

# Beta Infrastructure and Release Readiness

> This checklist applies to the **`v2.1.0-beta.*`** release family. It is not a historical inventory of one server and not a replacement for a private operations runbook. Private addresses, credentials, and incident data stay outside the public repository.

## 1. Gate objective

A beta candidate is ready only when a new tester or maintainer can complete:

```text
clean clone
→ release artifact verification
→ first-run
→ login + 2FA according to profile
→ baseline content workflow
→ quality/security gate
→ backup + restore evidence
→ documented rollback
```

“Works on the primary development server” is not an acceptance criterion. We need a reproducible path without a hidden local file or a manual database repair—which is at least easier when there is no SQL database. 😄

## 2. Release identity

Record before the gate:

| Field | Value |
|---|---|
| commit SHA | exact candidate commit |
| tag/version | planned tag or immutable build ID |
| build timestamp | UTC |
| backend runtime | PHP + Composer lock |
| frontend runtime | Node/npm + lockfile |
| deployment profile | Docker, single-node, demo, split test |
| artifact checksum | SHA-256 release ZIP/tar/image digest |
| migration range | source version used in upgrade test |

The word “latest” is not evidence. A candidate must remain identifiable three months later.

## 3. Clean-clone acceptance

On a clean host or disposable VM:

```bash
git clone <repository-url> paginiumcms
cd paginiumcms
git switch <candidate-tag-or-sha>
export FIRST_ADMIN_EMAIL='beta-admin@example.test'
export FIRST_ADMIN_PASSWORD='Unique-Temporary-Beta-Password'
export FIRST_ADMIN_NAME='Beta Administrator'
./scripts/first-run.sh
```

Acceptance:

- [ ] `.env` is created without overwriting an existing file,
- [ ] `APP_KEY` is real, not a placeholder,
- [ ] storage tree has correct owner and mode,
- [ ] the first admin is created only once,
- [ ] diagnose/rebuild completes without damaging the SSOT,
- [ ] `/api/health` succeeds,
- [ ] admin login and dashboard work without `500`,
- [ ] a mutation without CSRF is rejected,
- [ ] an unauthorized account cannot perform an admin mutation,
- [ ] logs contain no bootstrap password or secret.

## 4. Required quality gate

Minimum backend:

```bash
composer test
composer stan
composer cs
composer audit
```

Minimum frontend:

```bash
cd frontend
npm ci
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

Project gate:

```bash
cd ..
./scripts/iteration-gate.sh
# expanded runner when present in the candidate
./scripts/run-all-tests.zsh
```

The CI workflow should mirror the release minimum. A local green run is not release evidence when it uses a different lockfile, PHP version, or environment.

## 5. CI matrix

Recommended jobs:

| Job | Required checks |
|---|---|
| Backend | Composer install, PHPUnit, PHPStan L8, syntax, dependency audit |
| Frontend | `npm ci`, type-check, ESLint, API barrel, Vitest, production build, audit policy |
| Integration | bootstrap test, HTTP smoke, auth/CSRF/RBAC, storage diagnostics |
| Security | extension policy packs, traversal/ZIP, SSRF, log sanitization, secret scan |
| Docs | Markdown links, code fences, SK/EN path/heading parity for changed docs |
| Artifact | build, checksum/SBOM according to release process, archive-content check |

A Newman/Postman smoke collection can supplement the gate. It is not a complete API specification or a replacement for PHPUnit contract tests.

## 6. Beta smoke scenario

### 6.1 Authentication and authorization

- [ ] login success/failure and rate limit,
- [ ] session regeneration and logout,
- [ ] staff 2FA in production profile,
- [ ] password reset without account enumeration,
- [ ] USER/EDITOR/ADMIN/SUPER_ADMIN boundaries,
- [ ] Path ACL allow/deny and recovery from a bad rule.

### 6.2 Content

- [ ] create a draft,
- [ ] edit and publish according to implemented lifecycle,
- [ ] reload preserves data,
- [ ] a second editor triggers a lock/revision conflict,
- [ ] conflict is not silently overwritten,
- [ ] trash/restore or delete workflow according to version,
- [ ] media upload and safe delivery.

### 6.3 Operations

- [ ] firewall/WAF event is visible,
- [ ] audit captures a significant mutation,
- [ ] log rotation/retention does not break the active writer,
- [ ] backup is created and restore verified in a separate tree,
- [ ] scheduler/worker processes an allowed job,
- [ ] retry creates no duplicate,
- [ ] maintenance/recovery path is documented.

### 6.4 Extensions

- [ ] a valid plugin imports as disabled,
- [ ] traversal/symlink/forbidden-PHP package is rejected,
- [ ] enable/disable changes only expected runtime state,
- [ ] uninstall/rollback does not remove unrelated data without confirmation,
- [ ] frontend extension documents build/redeploy requirements.

## 7. Security baseline

| Area | Release requirement |
|---|---|
| Transport | HTTPS for public/staff deployment; HSTS according to domain rollout plan |
| Docroot | only `backend/public/`; authoritative data outside public root |
| Environment | `APP_ENV=production`, `APP_DEBUG=false`, demo fail-closed |
| Session | Secure/HttpOnly/SameSite for topology; trusted proxy explicit |
| CSRF | synchronizer token on session mutations |
| RBAC/ACL | backend permission plus Path ACL tests |
| Secrets | real `APP_KEY`, encrypted secret fields, no secrets in logs/UI |
| Upload/ZIP | size/type/content/path limits, SVG/HTML policy, Zip-Slip/symlink denial |
| Outbound | SSRF guard, HTTPS, timeout, redirect revalidation |
| Dependencies | Composer/npm audit according to approved severity policy |
| Logging | CR/LF/ANSI/CSV sanitization, retention, access permissions |
| Backup | encrypted/restricted access, restore test, copy outside primary host |

Security review details: [SECURITY_REVIEW.md](../SECURITY_REVIEW.md), root `SECURITY.md`, and [developer/SECURITY.md](SECURITY.md).

## 8. Scheduler and worker

The concrete release states:

- scheduler command name,
- worker command name,
- interval or queue trigger,
- lock, maximum runtime, and stale-lock recovery,
- retry/backoff and dead-letter/failure state,
- job identity/permission context,
- logging/audit and monitoring,
- safe deploy/restart procedure.

Use [deploy/CRON.md](../deploy/CRON.md), not an old server IP from a historical issue. A worker must not receive implicit SUPER_ADMIN authority merely because it runs on the server.

## 9. Deployment smoke

After deployment:

```bash
curl --fail --silent https://example.test/api/health
curl -I https://example.test/
curl -I https://example.test/storage/app/content/data/users/
```

Expectations:

- health succeeds,
- SPA assets have correct content type/cache policy,
- sensitive storage path is `404`/inaccessible,
- security headers match the current profile,
- old assets are not mixed with a new manifest,
- PHP-FPM/opcache and workers use the new release,
- migration/rebuild completes before mutations reopen.

## 10. Backup, upgrade, and rollback gate

Before tagging, verify at least:

1. backup of authoritative content/config/user storage,
2. preservation of `APP_KEY` and required secrets separately from the data archive,
3. upgrade from an approved previous beta version,
4. idempotent repeated migration,
5. index/cache rebuild,
6. application rollback,
7. data restore into a separate test root,
8. login and one content record after restore.

Rollback is not merely “revert the Git commit” after a schema change. It defines format compatibility or a restore point.

## 11. Release evidence package

Archive or attach:

- artifact checksum,
- CI run URL/ID,
- test summary without inflated historical counts,
- dependency audit summary and accepted exceptions,
- migration/rollback result,
- clean-clone smoke record,
- security review delta,
- known limitations,
- release notes and changelog,
- open blocker list with owners.

Evidence must not contain `.env`, cookies, TOTP QR, API keys, private URLs, or tester personal data.

## 12. Severity and release decision

| State | Decision |
|---|---|
| Critical/high exploitable finding without mitigation | stop release |
| SSOT loss or corruption | stop release |
| Authz/CSRF bypass | stop release |
| Non-reproducible first-run/upgrade | stop release |
| Flaky test in a security path | stop and fix deterministically |
| Documentation mismatch without security impact | may ship as an explicit known issue by maintainer decision |
| Cosmetic UI bug | triage according to impact and beta objective |

“Beta” does not mean accepting a known authorization bypass. It means interfaces and non-blocking capabilities can change transparently.

## 13. Incident and hotfix

After a release regression:

1. stop further rollout,
2. identify exact artifact and scope,
3. activate rollback or maintenance according to impact,
4. preserve redacted logs/audit,
5. create an `ISS-xxx` detail with cause and solution,
6. add a regression test,
7. publish a hotfix with a new immutable tag,
8. update changelog/security advisory as needed.

Iteration 13 will make issue numbers in `ISSUES.md` clickable to stable explicit anchors for each detail.

## 14. Beta onboarding path

| Order | Document |
|---|---|
| 1 | [LOCAL_SETUP.md](LOCAL_SETUP.md) |
| 2 | [user/INSTALLATION.md](../user/INSTALLATION.md) |
| 3 | [user/FIRST_STEPS.md](../user/FIRST_STEPS.md) |
| 4 | [user/BETA_TESTER.md](../user/BETA_TESTER.md) |
| 5 | [TESTING.md](TESTING.md) |
| 6 | [deploy/DEPLOY.md](../deploy/DEPLOY.md) and [CRON.md](../deploy/CRON.md) |

## 15. Final release checklist

- [ ] immutable commit/tag and checksum,
- [ ] clean-clone acceptance,
- [ ] backend/frontend/CI gate green,
- [ ] security baseline green,
- [ ] content concurrency smoke,
- [ ] extension policy smoke,
- [ ] backup + restore evidence,
- [ ] upgrade + rollback evidence,
- [ ] production configuration review,
- [ ] docs, changelog, and known issues current,
- [ ] no open blocker without an explicit decision,
- [ ] monitoring and incident owner ready.

Related: [PUBLIC_BETA1.md](../PUBLIC_BETA1.md), [CONTRIBUTING.md](CONTRIBUTING.md), [TESTING.md](TESTING.md), [SECURITY_REVIEW.md](../SECURITY_REVIEW.md).
