# PaginiumCMS — Public Beta 1

> **Release family:** `v2.1.0-beta.*`  
> **Recommended tag in this documentation snapshot:** **`v2.1.0-beta.23`**  
> **Status:** public beta for technical testers, early adopters, and security review  
> **Data model:** no SQL database; files are the source of truth

`v2.1.0-beta.1` marks the start of Public Beta. Later beta tags are cumulative fixes and feature additions. For a new test, do not use old `beta.1` merely because it is named “Beta 1”; use the latest verified beta tag of the project.

---

## 1. What the beta validates

Public Beta validates that PaginiumCMS can be:

- installed from a clean clone,
- securely configured without an SQL database,
- used to manage pages, articles, media, and navigation,
- operated with cron jobs, backups, and logs,
- updated and diagnosed according to documentation,
- tested outside the maintainer's development environment.

Beta is not a promise of API stability or a guarantee of defect-free operation. Perform your own security and operations review before production deployment.

---

## 2. Features included in `beta.23`

| Area | Status |
|------|--------|
| Session auth, 2FA, RBAC/ACL | ✅ |
| Pages/articles, Markdown, and Tiptap | ✅ |
| Locks, drafts, versions, and conflicts | ✅ |
| SEO, blog, feeds, and SK/EN i18n | ✅ |
| DAM, navigation, comments, and contact | ✅ |
| Newsletter and feature gallery | ✅ |
| Scheduler, scheduled publishing, backups, and trash | ✅ |
| WAF, audit, logs, and security hardening | ✅ ongoing |
| Plugin runtime, Code Policy, Developer Mode | ✅ foundation |
| System update and demo sandbox | ✅ |
| Layout Switch It.58c | ✅ |
| Hybrid Engine It.68–77 | ⏳ not part of beta.23 |

Complete inventory: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 3. Tester quick start

1. Read [user/INSTALLATION.md](user/INSTALLATION.md).
2. Run `./scripts/first-run.sh` for the selected profile.
3. Start the Docker stack or local PHP/Vite development mode.
4. Verify `GET /api/health`.
5. Sign in, replace the bootstrap password, and enable 2FA.
6. Follow [user/FIRST_STEPS.md](user/FIRST_STEPS.md).
7. Run the beta checklist in [user/BETA_TESTER.md](user/BETA_TESTER.md).

Example:

```bash
git checkout v2.1.0-beta.23
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
```

Verify ports and commands against the current compose/env files; the documentation example may not match a custom deployment profile.

---

## 4. Minimum smoke test

- login, logout, and 2FA,
- create a draft page and article,
- concurrent editing or a simulated revision conflict,
- upload an image and insert it into the editor,
- publish and view on the public site,
- comment/contact/newsletter flow for enabled modules,
- create and verify a backup,
- move an item to trash and restore it,
- manually run a scheduler job,
- inspect logs, audit trail, and WAF module,
- switch language and appearance/layout settings.

Record the exact version, commit, and deployment mode.

---

## 5. Operational prerequisites

| Topic | Requirement |
|-------|-------------|
| HTTPS | mandatory for a public/production instance |
| `APP_KEY` | stable, secret, and backed up according to security documentation |
| Permissions | storage writable by the PHP container/process user |
| Cron | `scheduler:run` and worker per [deploy/CRON.md](deploy/CRON.md) |
| Backup | verify restoration, not only archive creation |
| Mail/connectors | test against a real but safe testing destination |
| Reverse proxy | correct trusted proxies, headers, and body limits |
| Logs | `display_errors=Off` in production; errors go to logs |

---

## 6. Known boundaries that are not regressions

- The It.25 setup wizard is not shipped; onboarding uses `first-run.sh` and guides.
- The full It.68–77 Hybrid/Git-headless engine is a plan, not a beta.23 feature.
- Redis, S3, cloud translation, and the AI agent are neither mandatory nor active.
- Some integrations become usable only after admin and infrastructure configuration.
- Cron-dependent workflows do not run automatically without host cron/systemd/worker setup.
- The theme/runtime model is partial and remains subject to It.67 and later waves.

---

## 7. Reporting defects

For a regular bug report, include:

- tag/version and commit,
- OS, PHP/Node versions, and deployment profile,
- reproduction steps,
- expected and actual behavior,
- relevant logs without secrets or personal data,
- health/diagnose output,
- whether the issue occurred on a clean install or after an update.

### Security findings

Do not publish an unpatched vulnerability as a normal public issue. Follow the process in root [`SECURITY.md`](../SECURITY.md). Remove passwords, tokens, cookies, personal data, and production content before sharing evidence.

---

## 8. Highest-value test areas

1. clean installation and permissions,
2. auth/CSRF/2FA and role boundaries,
3. concurrent writes and failure recovery,
4. backup/restore and update rollback,
5. cron-dependent jobs,
6. plugin/theme/import boundaries,
7. nginx/reverse proxy headers,
8. documentation — whether commands work on a machine other than the maintainer's.

---

## 9. After Public Beta

The nearest direction after documentation completion:

- It.68 Hybrid Engine foundation,
- It.69 cache/Redis/HTTP validators,
- It.67 untrusted surfaces hardening,
- It.58d layout polish,
- community beta fixes,
- It.25 pre-Final onboarding/update UX.

Roadmap: [ROADMAP.md](ROADMAP.md) · Active handoff: [CONTINUATION.md](CONTINUATION.md).
