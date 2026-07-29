# Iteration 63 — Admin system update (production only)

**Status:** ✅ **v2.1.0-beta.18** — MVP + v2 compare UI + one-click deploy latest tag  
**Target release:** `v2.1.0-beta.18`

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

- `SystemDeployServiceTest`, `SystemUpdateControllerTest`, `SystemUpdateVersionMatcherTest`, `GitHubReleaseClientTest`, `GitHubReleaseWebhookVerifierTest`, `GitHubReleaseWebhookControllerTest`
- `JobRegistryStoreTest`, `JobsControllerPrivilegedDeployTest`, `PrivilegedJobPolicyTest`

## Phases (post-v2)

### v2 ✅ (shipped `v2.1.0-beta.18`)

- One-click deploy latest tag button (confirm + primary CTA when update available)
- Rich compare UI — commit table (SHA, message, author, date) from GitHub compare API (max 50, tag-aware head)

### v3 ✅ (shipped `v2.1.0-beta.18`)

- Webhook `POST /api/webhooks/github/release` on **release published** (HMAC `X-Hub-Signature-256`)
- Settings: `webhookDeployEnabled`, `githubWebhookSecret` (encrypted at-rest)
- Auto-enqueues `system-deploy` job; idempotent skip when same tag recently succeeded
- Exempt from CSRF; allowed during maintenance mode; WAF body-scan exempt

### v4 ⏳ deferred → [Iteration 25](ITERATION_25.md) (pre-Final 1.0)

End-user **Grav-like** polish (not beta scope):

- Dashboard “update available” banner + primary **Update now** CTA
- Setup wizard step for GitHub token / deploy enable / permissions checklist
- Human-readable deploy progress; webhook + manual ref under Advanced/Ops
- Optional pre-update backup prompt

Technical deploy engine stays in It.63; UX and onboarding move to It.25 as a **late pre-Final gate**.

## Production enable checklist

1. Settings → **System update** — GitHub token (`public_repo`), enable `deployEnabled`
2. Prefer tag deploy (`allowDeployTags=true`); branch deploy only when intentional
3. **App `.env`:** `APP_ROOT=/var/www/html`, `STACK_DIR`, `BACKEND_PORT`, `DEMO_MODE=false`
4. **Docker bootstrap (once):** `APP_ROOT=/var/www/paginiumcms.com ./scripts/bootstrap-deploy-permissions.sh`
5. Deploy via tag ref (e.g. `v2.1.0-beta.15`)
6. **Remote check:** Platform → System update → *Skontrolovať remote* — verify banner + release notes from GitHub
7. **Webhook (optional auto-deploy):** Settings → enable `webhookDeployEnabled`, set `githubWebhookSecret`; GitHub → Webhooks → URL `https://your-domain/api/webhooks/github/release`, content type JSON, secret, event **Release** only

## Related docs

- [deploy/DEPLOY.md §G6](deploy/DEPLOY.md) — Docker admin deploy
- [developer/RELEASE.md](developer/RELEASE.md) — tag workflow
- [ISSUES.md](ISSUES.md) — ISS-104, ISS-105
