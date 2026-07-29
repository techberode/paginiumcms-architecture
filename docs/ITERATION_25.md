# Iteration 25 — Setup wizard + one-click updates (pre-Final gate)

**Status:** ⏳ Planned — **late pre-Final 1.0** (not Public Beta scope)  
**Version target:** post-beta, before **1.0 GA**  
**Priority:** 🟡 medium — one of the **last major steps** before Final

## Product goal

First-run onboarding **and** user-facing CMS updates: when a newer release exists, the site owner (SUPER_ADMIN) sees it and can **start the update in one click** — similar in *feel* to Grav/other CMS admin updaters, without copying their implementation rules.

**Not in Beta:** Beta keeps [Iteration 63](ITERATION_63.md) as the **technical foundation** (remote check, deploy job, webhook for ops). It.25 polishes onboarding + end-user UX before GA.

## Relationship to It.63 (system update)

| Layer | Owner | Status |
|-------|--------|--------|
| Backend deploy job, GitHub compare, webhook | It.63 | ✅ shipped `v2.1.0-beta.18` |
| Dashboard “update available” + one-click CTA | It.25 | ⏳ planned |
| Setup wizard: GitHub token, deploy enable, permissions hint | It.25 | ⏳ planned |
| Package-based update (no git on server) | It.25 stretch / 1.0+ | ⏳ optional |

It.63 **v4 (Grav-like UX)** is **merged into It.25** — no separate iteration.

## Setup wizard scope

| # | Deliverable |
|---|-------------|
| 1 | Route `/setup` when install not marked complete (`general.installed` or equivalent) |
| 2 | **Step 1 — Admin:** first SUPER_ADMIN (or env bootstrap confirmation) |
| 3 | **Step 2 — Site profile:** site name, locale, optional stock image topic → `general.*`, `media.stockImageTopic` |
| 4 | **Step 3 — Updates (optional):** detect git vs package install; if git — GitHub owner/repo, read token, enable `deployEnabled`, link to `bootstrap-deploy-permissions.sh` checklist |
| 5 | **Step 4 — Optional seed:** N stock images via `StockImageImporter` (It.24) |
| 6 | `POST /api/setup/complete` — atomic write settings + `installed: true` |
| 7 | Redirect to admin dashboard |

**Note (2026-07):** Stock image topic alone is already in Settings → Media; wizard step 2/4 replaces ad-hoc first-run only when full It.25 ships.

## One-click update UX (Final-facing)

| # | Deliverable |
|---|-------------|
| 1 | **Dashboard banner** — “Update {version} available” (cached remote check, SUPER_ADMIN only) |
| 2 | **Primary action** — “Update now” → confirm → reuse It.63 deploy job (latest release tag) |
| 3 | **Progress panel** — human-readable job log (not raw JSON); success / failed / skipped |
| 4 | **Advanced** — manual ref, commit table, webhook block moved under collapsible “Operations” (Platform → System update stays for power users) |
| 5 | **Pre-update** — optional backup prompt (`storage/app` snapshot or link to Backup module) |
| 6 | **Demo / non-git** — clear message: manual update path ([INSTALLATION.md](user/INSTALLATION.md)); package download mode = stretch goal |

## Security & roles (unchanged baseline)

- SUPER_ADMIN + 2FA for setup complete and any deploy trigger
- CSRF on mutating setup endpoints
- Secrets in `SettingsSchema` as `password` (encrypted at-rest)
- No arbitrary shell — whitelisted deploy script only (It.63)
- Demo instance: update UI hidden (`DEMO_MODE=true`)

## Depends on

- [Iteration 24](ITERATION_24.md) — stock catalogue + `StockImageImporter` ✅
- [Iteration 63](ITERATION_63.md) — system update MVP + v2/v3 ✅
- Public Beta tester feedback + stability gate before starting It.25

## Out of scope (Beta / pre-It.25)

- Full community testing pass on beta tags
- Package-based one-click update without git (document as 1.0 stretch if not in It.25)
- Automatic update on every GitHub release without SUPER_ADMIN confirm (webhook stays opt-in ops)

## Acceptance (pre-Final checklist)

- [ ] Fresh install completes wizard without CLI beyond `first-run.sh` / Docker
- [ ] Git-based install: remote check + one-click update from dashboard
- [ ] Content under `storage/app/content/` unchanged after update
- [ ] Demo instance never shows update CTA
- [ ] Docs: [INSTALLATION.md](user/INSTALLATION.md), [ADMIN_GUIDE.md](user/ADMIN_GUIDE.md) — “Updating PaginiumCMS”

## See also

- [ITERATION_24.md](ITERATION_24.md) — stock library
- [ITERATION_63.md](ITERATION_63.md) — deploy engine (beta)
- [deploy/DEPLOY.md](deploy/DEPLOY.md) — server requirements
- [PUBLIC_BETA1.md](PUBLIC_BETA1.md) — beta scope (It.25 explicitly excluded)
