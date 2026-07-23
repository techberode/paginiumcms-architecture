# Security Policy

PaginiumCMS is in **Public Beta** (`v2.1.0-beta.2`). We welcome responsible disclosure from security researchers and beta testers.

## Supported versions

| Version | Supported |
|---------|-----------|
| `v2.1.0-beta.2` | ✅ Current (recommended for review) |
| `v2.1.0-beta.1` | ⚠️ Superseded — missing audit-trail CSV sanitization fix |
| `2.0.x` | Maintenance only — use beta.2 for security review |

## Reporting a vulnerability

**Do not** open public GitHub Issues for unpatched security bugs.

1. Email the maintainer (contact in GitHub profile / repository owner).
2. Include: affected version/tag, steps to reproduce, impact, and optional PoC.
3. Allow reasonable time to fix before public disclosure.

We will acknowledge receipt and keep you updated on status.

## What to review

| Document | Purpose |
|----------|---------|
| [docs/SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md) | **External reviewer guide** — attack surface, endpoints, test plan |
| [docs/developer/SECURITY.md](docs/developer/SECURITY.md) | Architecture — auth, CSRF, WAF, encryption, plugins |
| [docs/ISSUES.md](docs/ISSUES.md) | Public incident log (ISS-001–077) |
| [docs/PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md) | Beta scope and known limitations |
| [docs/architecture/CORE_HARDENING.md](docs/architecture/CORE_HARDENING.md) | RBAC, maintenance, storage |

## Scope

**In scope:** PaginiumCMS application code in this repository (PHP backend, React admin SPA, Docker/dev scripts, documented deployment).

**Out of scope (unless chained with in-app bug):**

- Third-party hosting misconfiguration (missing HTTPS, exposed `.env`)
- Social engineering
- Denial-of-service at network edge without application flaw
- Issues in dependencies already fixed in latest supported tag (check `composer audit` / `npm audit`)

## Safe testing

- Use a **local clone** with `./scripts/first-run.sh` — do not test against production without written permission.
- Default dev credentials (`admin@localhost`) are for **local installs only**; change before any network exposure.
- Demo mode may expose demo login hints in public settings — intended for showcase instances only.

## Security baseline (summary)

- Session auth + Argon2id + optional TOTP 2FA
- RBAC (`RoleMiddleware`) + fine-grained permissions (`PermissionMiddleware`)
- CSRF synchronizer token on mutating API calls (SPA)
- WAF middleware (URI, query, User-Agent, bounded JSON body)
- Encryption at-rest for TOTP seeds and settings secrets (`APP_KEY`)
- SSRF guard on outbound HTTP to admin-configured URLs
- Path ACL for content/media; storage allow-list for public files
- Plugin code policy + Zip-Slip checks on extension import

Details: [docs/developer/SECURITY.md](docs/developer/SECURITY.md).
