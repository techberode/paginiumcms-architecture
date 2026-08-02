---
title: User Guide
description: Entry point for deploying, administering, editing, and beta-testing PaginiumCMS
icon: material/book-open-page-variant
---

# PaginiumCMS User Guide

> This documentation branch describes user and administrator workflows for the **`v2.1.0-beta.*`** release family. Always verify the exact installation tag in release notes and `CHANGELOG.md`.

PaginiumCMS is evolving into a **Hybrid Headless Content Engine**, while files remain the mandatory source of truth. The user guide therefore separates current beta workflows from capabilities planned for It.68–77.

## 1. How to use this guide

| Order | Document | Audience |
|---|---|---|
| 1 | [Installation](INSTALLATION.md) | server operator, maintainer, beta tester |
| 2 | [First steps](FIRST_STEPS.md) | new administrator or editor |
| 3 | [Administrator guide](ADMIN_GUIDE.md) | day-to-day CMS management |
| 4 | [Content editor](CONTENT_EDITOR.md) | page and article editors |
| 5 | [Permissions and Path ACL](ACCESS_CONTROL.md) | SUPER_ADMIN and security operator |
| 6 | [Firewall](FIREWALL.md) and [Logging](LOGGING.md) | operations and incident response |
| 7 | [Beta tester](BETA_TESTER.md) | functional and security testing |

Additional guides cover [branding](BRANDING.md), [plugins](PLUGINS.md), [themes](THEMES.md), [Code Editor](CODE_EDITOR.md), and [Developer Mode](DEVELOPER_MODE.md).

## 2. Status labels

| Label | Meaning |
|---|---|
| **Implemented** | workflow is part of the current beta branch; a particular build may contain a fix or small UI difference |
| **Transitional** | capability exists, but its contract or screen is still being consolidated |
| **Planned** | target It.68–77 capability; do not assume availability without release-note confirmation |
| **Environment-gated** | visible only with the required role, configuration, or deployment profile |

When the UI and guide disagree, the concrete release, API response, and server logs are authoritative. Report the difference as a documentation bug.

## 3. System mental model

```text
browser / React admin
        ↓ HTTP API
Slim/PHP adapters and middleware
        ↓ application services
flat-file SSOT
        ↓ derived layers
index, cache, audit, Git/translation/AI jobs
```

- The admin panel is not the source of truth; it renders state loaded through the API.
- Authoritative content and configuration live in allowed storage paths.
- Indexes and caches are rebuildable and must not replace authoritative files.
- Git publishing, translation, and AI are separate downstream actions, not an automatic part of every save.

## 4. Roles and responsibility

| Role | Typical use | Important boundary |
|---|---|---|
| `USER` | public account, profile, comments according to settings | normally has no administration access |
| `EDITOR` | pages, articles, media, and navigation | must not manage platform secrets or security policy |
| `ADMIN` | users, settings, inbox, and operations modules | does not automatically bypass Path ACL or extension policy |
| `SUPER_ADMIN` | RBAC, Path ACL, extensions, and critical configuration | assign only to a small set of trusted accounts |

A SUPER_ADMIN can customize exact permissions. A role name alone is therefore not proof of authorization; the backend permission check is decisive.

## 5. Safe operational baseline

Before exposing an instance outside a local network:

1. replace the bootstrap password and enable 2FA for staff accounts,
2. set `APP_ENV=production` and `APP_DEBUG=false`,
3. use HTTPS and configure `TRUSTED_PROXIES` correctly,
4. create and test a backup of authoritative storage,
5. configure cron/workers required by the concrete release,
6. review firewall, logs, audit, and retention,
7. remove demo/test accounts and disable `DEMO_MODE`,
8. never use `chmod 777` as a universal permissions fix.

## 6. Where to report a problem

- Regular bug: check [ISSUES.md](../ISSUES.md), then create a public issue with reproduction steps.
- Security finding: follow the root `SECURITY.md`; do not publish an unpatched vulnerability.
- Documentation mismatch: include document path, release tag, screen/endpoint, and expected behavior.

Never attach `.env`, session cookies, API keys, TOTP secrets, private keys, or unredacted logs containing personal data.

## 7. Related technical documentation

- [API architecture](../architecture/API.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Storage](../architecture/STORAGE.md)
- [Versioning and conflicts](../architecture/VERSIONING.md)
- [Deployment modes](../architecture/DEPLOYMENT_MODES.md)
- [Public Beta](../PUBLIC_BETA1.md)
