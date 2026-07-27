# Iteration 63 — Admin system update (production only)

**Status:** ⏳ Planned (next iteration after It.13 v3)  
**Target release:** `v2.1.0-beta.10` or post-beta patch

## Product position

**Goal:** SUPER_ADMIN triggers CMS code deploy from admin UI, backed by GitHub — **without** shell access for routine production updates.

| Instance | Admin update button |
|----------|---------------------|
| Production (`paginiumcms.com`) | ✅ when `deployEnabled` |
| Demo (`demo.paginiumcms.com`) | ❌ hidden — demo stays SSH + reset workflow |
| Customer production | ❌ module never ships in customer bundle |

This is **code deploy** (git tag / `main`), not content sync (`/github` panel).

## Summary

| Layer | Deliverable |
|-------|-------------|
| **UI** | `Platform → System update` (`/platform/update`) — current ref, remote SHA/tag, changelog snippet, deploy actions, job log |
| **API** | `GET /api/admin/system/update/status`, `POST …/check`, `POST …/run` |
| **CLI** | `php backend/bin/console system:deploy --ref=origin/main` |
| **Execution** | Job queue → root-owned wrapper → [`scripts/deploy-instance-update.sh`](../scripts/deploy-instance-update.sh) |
| **Settings** | Group `systemUpdate` — GitHub owner/repo/token (encrypted), `deployEnabled`, tag/branch policy |
| **Security** | SUPER_ADMIN + 2FA + CSRF; no user-controlled shell; audit log on every run |

## Phases

### MVP

- Status endpoint (current `git describe`, remote compare via GitHub API)
- Manual deploy enqueue from admin (job queue, same outcome UX as It.62)
- CLI `system:deploy` for worker/cron

### v2

- GitHub compare UI (commits behind/ahead)
- One-click deploy latest tag or `main`

### v3

- Webhook `POST /api/webhooks/github/release` on published release (HMAC secret in settings)

## Dependencies

- ✅ It.62 — scheduler prod hardening, job outcome UX
- ✅ [`docs/deploy/DEPLOY.md`](deploy/DEPLOY.md) — manual deploy C&P
- ✅ [`scripts/deploy-instance-update.sh`](../scripts/deploy-instance-update.sh)

## Related docs

- [deploy/DEPLOY.md §G](deploy/DEPLOY.md#g-planned-admin-system-update-production-only) — full spec
- [developer/RELEASE.md](developer/RELEASE.md) — tag workflow
- [ITERATION_13.md](ITERATION_13.md) — demo instance (explicitly **no** admin deploy)

## Out of scope

- Demo instance upgrade via admin
- Customer-hosted auto-update without explicit SUPER_ADMIN action
- Replacing SSH for first-time server bootstrap
