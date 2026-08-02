---
title: Beta Tester Guide
description: Reproducible functional, operational, and security testing of PaginiumCMS
icon: material/test-tube
---

# Beta tester working guide

> Test an exact tag from the **`v2.1.0-beta.*`** release family. Do not write only “latest” in a report; after another release nobody will know what you tested.

## 1. Beta-test scope

Beta testing should validate:

- clean installation and first login,
- routine content editing,
- permission and security boundaries,
- recovery and operations workflows,
- separation of implemented and planned Hybrid Engine capabilities,
- documentation clarity.

Beta does not authorize testing somebody else's public instance. Perform security tests only in your own or explicitly authorized environment.

## 2. Record the environment before testing

```text
PaginiumCMS tag/commit:
Installation: Docker | native | VPS
OS and architecture:
PHP version:
Node version (when relevant):
Web server / proxy:
Browser:
APP_ENV:
Reproduces on clean data: yes/no
```

Publish secrets, internal hostnames, or public IPs only when necessary and safe.

## 3. Quick smoke test

| # | Task | Result |
|---|---|---|
| 1 | install using [INSTALLATION.md](INSTALLATION.md) | [ ] |
| 2 | `/api/health` succeeds | [ ] |
| 3 | bootstrap login and password replacement | [ ] |
| 4 | 2FA setup and second login | [ ] |
| 5 | dashboard without an unhandled 500 | [ ] |
| 6 | page: draft → preview → publish | [ ] |
| 7 | article with tag and image | [ ] |
| 8 | media upload and public rendering | [ ] |
| 9 | navigation and anonymous site check | [ ] |
| 10 | audit/log matches the action | [ ] |
| 11 | backup creation and verification | [ ] |
| 12 | cron/worker according to profile | [ ] |

## 4. Editor and conflict tests

Use two independent sessions:

1. open the same content in both,
2. verify lock warning/heartbeat,
3. save from the first session,
4. attempt to save the stale version from the second,
5. confirm that the newer version is not silently overwritten,
6. document conflict resolver or error behavior.

Include request status and redacted response `code`/`requestId` when provided by the build.

## 5. Roles and ACL

Create `EDITOR` and, when needed, `ADMIN` accounts. Test:

- an allowed action in the UI,
- the same allowed action through the API,
- a forbidden mutation through a direct API request,
- an allowed and denied Path ACL path,
- 404 for hidden existence versus 403 for denied write,
- SUPER_ADMIN bypass according to the documented contract.

Do not treat UI-only button hiding as a successful security test.

## 6. Operations tests

- disable or break a derived cache and verify recovery/rebuild,
- simulate full or read-only storage only in an isolated environment,
- verify behavior when an outbound provider is unavailable,
- stop a worker and observe job state without repeated triggering,
- perform a restore test into a separate path,
- verify that logs/backups/data are not directly web-accessible.

## 7. Firewall and logging

On your own instance, use a safe probe such as a nonexistent WordPress path and inspect the incident/jail according to configuration. Do not use destructive payloads or scan third-party networks.

Verify that:

- WAF does not block normal editor content,
- client IP is correct behind a proxy,
- auth endpoints do not log passwords or TOTP values,
- log retention and purge work,
- a plain-text WAF 403 does not break the frontend with an unconditional JSON parse.

## 8. Extension and Developer Mode tests

Use only a trusted reference package. Verify import → disabled state → explicit enable → smoke endpoint → disable. Frontend source may require build/redeploy; missing UI without a build is not automatically a backend bug.

Code Editor Save does not mean plugin enable, Git commit, or deployment. Report the exact missing step.

## 9. Planned, not necessarily implemented

Without release-note confirmation do not expect universal availability of:

- It.74 API key/JWT integration,
- It.69/71 Redis Performance Guard,
- It.72 S3 media driver,
- It.73 multilingual content document,
- It.75 AI agent,
- It.76/77 self-hosted/cloud translation,
- fully automated It.70 Git publishing.

When the UI contains a placeholder, report it as UX/docs only if it presents itself as a finished working capability.

## 10. Reporting a regular bug

Before creating an issue:

1. check [ISSUES.md](../ISSUES.md),
2. reproduce on a clean profile or fresh data,
3. test the latest confirmed beta tag,
4. separate configuration/proxy problems from application bugs.

A useful report contains:

```markdown
### Version and environment
...

### Reproduction steps
1. ...
2. ...

### Expected behavior
...

### Actual behavior
...

### Log / request ID
redacted excerpt

### Reproducibility
always | intermittent | once
```

## 11. Security finding

Do not create a public issue containing an unpatched exploit, token, or data dump. Follow the root `SECURITY.md`. Include impact, prerequisites, minimal reproduction, and a mitigation proposal; send no more personal data than necessary.

## 12. Documentation testing

Documentation is part of the product. Report:

- invalid links,
- stale tags or screenshots,
- endpoints missing from the release,
- planned capability presented as implemented,
- missing rollback or security warning,
- semantic differences between SK and EN.

Thank you — a beta tester who provides a clean reproduction saves a maintainer more time than ten messages saying “it doesn't work.” 🧪
