# Iteration 63 — Admin system update (production only)

**Status:** ✅ MVP shipped — **`v2.1.0-beta.12`**  
**Target release:** `v2.1.0-beta.12`

## Product position

**Goal:** SUPER_ADMIN triggers CMS code deploy from admin UI, backed by GitHub — **without** shell access for routine production updates.

| Instance | Admin update button |
|----------|---------------------|
| Production (`paginiumcms.com`) | ✅ when `deployEnabled` |
| Demo (`demo.paginiumcms.com`) | ❌ hidden — demo stays SSH + reset workflow |
| Customer production | ❌ module never ships in customer bundle |

This is **code deploy** (git tag / `main`), not content sync (`/github` panel).

## Summary (MVP)

| Layer | Deliverable |
|-------|-------------|
| **UI** | `Platform → System update` (`/platform/update`) — current ref, remote check, deploy actions, recent job runs |
| **API** | `GET /api/admin/system/update/status`, `POST …/check`, `POST …/run` |
| **CLI** | `php backend/bin/console system:deploy --ref=origin/main` |
| **Execution** | Job queue `system-deploy` → whitelisted [`scripts/deploy-instance-update.sh`](../scripts/deploy-instance-update.sh) |
| **Settings** | Group `systemUpdate` — GitHub owner/repo/token (encrypted), `deployEnabled`, tag/branch policy |
| **Security** | SUPER_ADMIN + 2FA middleware + CSRF; ref whitelist; audit log on every run; skipped in `APP_ENV=testing` and `DEMO_MODE` |

## Backend

- `GitRepositoryInspector` — local `git describe` / commit / branch
- `GitHubReleaseClient` — GitHub compare/releases (`OutboundUrlGuard`)
- `SystemDeployService` — validates ref, invokes deploy script only
- `SystemDeployHandler` — job handler `system.deploy`
- `SystemUpdateController` — status / check / run (enqueue + inline worker tick)
- `SystemDeployCommand` — CLI `system:deploy --ref=`

## Frontend

- `SystemUpdateView.tsx` + `frontend/src/api/systemUpdate.ts`
- Nav: Platform → **System update** (SUPER_ADMIN only, hidden on demo instance)
- Settings category **System** → group `systemUpdate`

## Tests

- `SystemDeployServiceTest` — ref validation, testing skip
- `SystemUpdateControllerTest` — auth, SUPER_ADMIN, deploy gate

## Phases (post-MVP)

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

## Production enable checklist

1. Settings → **System update** — set GitHub owner/repo/token, enable `deployEnabled`
2. Prefer tag deploy (`allowDeployTags=true`); branch deploy only when intentional (`allowDeployMain`)
3. Ensure host env: `APP_ROOT`, `STACK_DIR`, deploy script executable
4. Cron: `scheduler:run` + `worker:process` (same as It.62)
5. First deploy: use semver tag ref (e.g. `v2.1.0-beta.12`), verify audit log + job run history
