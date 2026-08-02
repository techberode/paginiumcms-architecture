---
title: Security Policy
description: Supported releases, responsible vulnerability disclosure, safe testing scope, and the PaginiumCMS security baseline
icon: material/security
---

# Security Policy

> PaginiumCMS is in **Public Beta**. This document governs public vulnerability reporting and safe testing. Technical invariants are defined in the [development security architecture](docs/developer/SECURITY.md), with a practical audit workflow in the [Security Review Guide](docs/SECURITY_REVIEW.md).

## 1. Supported releases

| Branch or release | Security status | Note |
|---|---|---|
| **`v2.1.0-beta.23`** | ✅ current documentation snapshot | Verify the exact tag, commit, and release notes. |
| A newer published `v2.1.0-beta.*` | ✅ preferred after verifying its release gate | Security fixes may be delivered only in a new beta tag. |
| An older `v2.1.0-beta.*` | ⚠️ superseded | Fixes may not be backported. Reproduce on the current tag first. |
| `2.0.x` | ⚠️ historical/maintenance branch | Do not assume the same hardening controls as `2.1.0-beta.*`. |
| `main`, a working tree, or an untagged commit | 🧪 development state | It is not a supported release artifact. |

If release notes or a GitHub Release declare a different status, the record for that exact tag takes precedence. A version number without its commit and checksum is not a complete release identity.

## 2. Private vulnerability reporting

Do not open a public GitHub Issue for an unpatched vulnerability.

1. Email **`security@paginiumcms.com`**.
2. Use `PaginiumCMS security report` and the affected version in the subject.
3. Include at least:
   - the exact tag or commit SHA,
   - deployment profile and relevant configuration without secrets,
   - reproduction steps,
   - expected and actual behavior,
   - estimated impact and required attacker privileges,
   - a safe PoC, log, or screenshot with sensitive data redacted.
4. If the finding contains live credentials, tokens, personal data, or production data, do not send them in clear text; agree on a secure channel first.

The contact address should also be published at `/.well-known/security.txt`. Verify both the mailbox and the public path before launch.

## 3. Coordinated disclosure

The project aims to:

- acknowledge the report,
- reproduce it on a supported tag,
- agree on severity and scope,
- add a regression test and a fix,
- publish a security tag or hotfix,
- then add a public record to [ISSUES.md](docs/ISSUES.md) and [CHANGELOG.md](CHANGELOG.md).

Timing depends on severity, reproducibility, and maintainer availability. A researcher must not publish a working exploit, secrets, or personal data before a coordinated fixed release.

## 4. Scope

### In scope

- the PHP backend and Slim middleware,
- the React admin SPA and public frontend,
- sessions, 2FA, CSRF, RBAC, and Path ACL,
- flat-file SSOT, storage routes, uploads, and media,
- Code Editor, plugin import, and extension runtime,
- WAF, logging, audit, and exports,
- outbound integrations and SSRF protection,
- Docker, nginx, and documented deployment scripts,
- CI/release workflow where a flaw can leak secrets or compromise an artifact,
- Hybrid Engine capabilities after they are actually implemented.

### Out of scope unless chained with an application flaw

- social engineering and phishing,
- physical access to the host,
- pure volumetric DDoS at the network edge,
- missing HTTPS, an exposed `.env`, or an open admin port caused by ignoring deployment documentation,
- a finding only in an unused optional dependency path without demonstrated reachability,
- an automated scanner report without reproduction or impact.

An operations misconfiguration may be accepted as a hardening recommendation without being classified as a product vulnerability.

## 5. Safe testing

- Test a local clone, isolated lab profile, or an instance for which you have written authorization.
- Do not use production accounts, real personal data, or third-party domains.
- Do not create a persistent backdoor, perform lateral movement, or retrieve more data than required to prove impact.
- Use minimal intensity for DoS testing and stop after confirming the symptom.
- Remove test accounts, tokens, plugins, jobs, and artifacts after testing.
- Sanitize logs before sharing them. TOTP seeds, QR payloads, provisioning URIs, OTPs, cookies, API keys, and bearer tokens must not appear in a public report.

The project treats research within these limits as good-faith activity when it respects privacy, service availability, and coordinated disclosure.

## 6. Security baseline

The current release-family baseline includes:

- Argon2id passwords, session regeneration, and optional TOTP 2FA,
- session cookies, a CSRF synchronizer token, and backend authorization,
- RBAC, permissions, and Path ACL,
- allow-listed public storage subtrees and path containment,
- encryption at rest for supported settings secrets and TOTP seeds through `APP_KEY`,
- rate limiting for login, OTP, and selected anonymous workflows,
- WAF checks for URI/query/User-Agent and bounded body scanning,
- outbound URL validation including environment-aware DNS and redirect rules,
- ZIP entry validation, Code Policy, and staged extension import,
- log sanitization, audit trail, and a security regression pack,
- a release gate, dependency audits, GitHub CI, and manual review of the complete local log.

The baseline does not prove that every tag includes every planned capability. Verify status against code, tests, and [FEATURE_OVERVIEW.md](docs/FEATURE_OVERVIEW.md).

## 7. Operator responsibility

The operator is responsible at minimum for:

- HTTPS and the correct reverse-proxy profile,
- a secret and preserved `APP_KEY`,
- secure storage ownership and permissions,
- explicit `TRUSTED_PROXIES`,
- production/demo isolation,
- backups outside the web root and regular restore tests,
- host, Docker runtime, and dependency updates,
- monitoring 5xx responses, authentication anomalies, WAF events, jobs, disk capacity, and backups,
- rotating secrets after an incident or log disclosure.

Application hardening does not replace a host firewall, TLS, a secure operating system, or server access management.

## 8. Security documents

| Document | Purpose |
|---|---|
| [Security Review Guide](docs/SECURITY_REVIEW.md) | Practical guide for an external auditor |
| [Development Security Architecture](docs/developer/SECURITY.md) | Threat model, trust boundaries, and technical invariants |
| [Testing](docs/developer/TESTING.md) | Quality gates and the security regression pack |
| [Release lifecycle](docs/developer/RELEASE.md) | 21-step gate, CI, log sanitization, and release evidence |
| [Audit report](AUDIT_REPORT.md) | Historical audit trail and current validation state |
| [Incident register](docs/ISSUES.md) | Public defects, causes, and fixes |
| [Deployment](docs/deploy/DEPLOY.md) | Production deployment, backup, smoke test, and rollback |
