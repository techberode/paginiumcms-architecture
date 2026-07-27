# Iteration 63 — Admin system update (production only)

**Status:** ✅ **v2.1.0-beta.15** — MVP + deploy hotfixes + v2 version check UX  
**Target release:** `v2.1.0-beta.15`

## Product position

**Goal:** SUPER_ADMIN triggers CMS code deploy from admin UI, backed by GitHub — **without** shell access for routine production updates.

| Instance | Admin update button |
|----------|---------------------|
| Production (`paginiumcms.com`) | ✅ when `deployEnabled` |
| Demo (`demo.paginiumcms.com`) | ❌ hidden — demo stays SSH + reset workflow |
| Customer production | ❌ module never ships in customer bundle |

This is **code deploy** (git tag / `main`), not content sync (`/github` panel).

## Summary (MVP + v2)

| Layer | Deliverable |
|-------|-------------|
| **UI** | `Platform → System update` — status, remote check with version banners + release notes, deploy, recent runs |
| **API** | `GET …/status`, `POST …/check` (returns `update`, `release_notes`, `release_url`), `POST …/run` |
| **CLI** | `php backend/bin/console system:deploy --ref=…` |
| **Execution** | Job `system-deploy` → whitelisted [`scripts/deploy-instance-update.sh`](../scripts/deploy-instance-update.sh) |
| **Settings** | Group `systemUpdate` — GitHub owner/repo/token (encrypted), `deployEnabled`, tag/branch policy |
| **Security** | SUPER_ADMIN + 2FA + CSRF; ref whitelist; audit log; **ISS-104** — jobs API cannot bypass SUPER_ADMIN for deploy |

## Backend

- `GitRepositoryInspector` — local `git describe` / commit / `commit_full` / branch
- `GitHubReleaseClient` — releases/latest + compare (`OutboundUrlGuard`); release body for UI
- `SystemUpdateVersionMatcher` — `current` / `update_available` / `unknown`
- `SystemDeployService` — validates ref, invokes deploy script only
- `PrivilegedJobPolicy` — `system.deploy` SUPER_ADMIN-only via jobs API; no cron auto-run
- `SystemUpdateController` — status / check / run

## Frontend

- `SystemUpdateView.tsx` — banners (aktuálna / dostupná aktualizácia), GitHub release notes, typed `SystemUpdateRemote`
- i18n SK/EN under `platform.systemUpdate.*`

## Tests

- `SystemDeployServiceTest`, `SystemUpdateControllerTest`, `SystemUpdateVersionMatcherTest`
- `JobRegistryStoreTest`, `JobsControllerPrivilegedDeployTest`, `PrivilegedJobPolicyTest`

## Phases (post-v2)

### v2 remaining

- One-click deploy latest tag button
- Rich compare UI (commit list)

### v3

- Webhook `POST /api/webhooks/github/release` on published release (HMAC secret in settings)

## Production enable checklist

1. Settings → **System update** — GitHub token (`public_repo`), enable `deployEnabled`
2. Prefer tag deploy (`allowDeployTags=true`); branch deploy only when intentional
3. **App `.env`:** `APP_ROOT=/var/www/html`, `STACK_DIR`, `BACKEND_PORT`, `DEMO_MODE=false`
4. **Docker bootstrap (once):** `APP_ROOT=/var/www/paginiumcms.com ./scripts/bootstrap-deploy-permissions.sh`
5. Deploy via tag ref (e.g. `v2.1.0-beta.15`)
6. **Remote check:** Platform → System update → *Skontrolovať remote* — verify banner + release notes from GitHub

## Related docs

- [deploy/DEPLOY.md §G6](deploy/DEPLOY.md) — Docker admin deploy
- [developer/RELEASE.md](developer/RELEASE.md) — tag workflow
- [ISSUES.md](ISSUES.md) — ISS-104, ISS-105
