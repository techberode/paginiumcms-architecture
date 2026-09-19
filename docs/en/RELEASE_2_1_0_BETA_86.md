# Release `v2.1.0-beta.86` — It.93o desk/staff + It.96 documents + remaining beta.85 CI

> **Date:** 2026-09-19  
> **Tag:** `v2.1.0-beta.86`  
> **Type:** feature + CI hotfix  
> **Previous:** [`v2.1.0-beta.85`](../../CHANGELOG.md#release-2-1-0-beta-85) — SQLite FTS token sanitization

`v2.1.0-beta.85` **already exists** and must not be moved. This is the next prerelease.

---

## One-line summary

Staff cards, Messenger desk, external-team chat with one-time invites, the document library, and the **GitHub CI failures that stayed red after beta.85**.

---

## What shipped

| Area | Change |
|------|--------|
| **It.93o-2** | Public-card social URLs must be verified (`POST /api/auth/me/social/verify`) before `GET /api/public/staff` lists them. |
| **It.93o-3** | `[staff-card]` / `[staff-team]` islands + visitor staff-chat into Messages. |
| **It.93o-4** | Contact subject → team/user routing; first reply claims the thread; Messenger inbox. |
| **It.93o-5** | Desk queue + on-page comment reply; bubble or in-item composer; hash jump `/comments#comment-…` / `/messages#message-…`. |
| **It.93o-6** | Desk reply mail from `@site-domain` only; required visitor e-mail (`VisitorEmailGuard`). |
| **It.93o-7** | External team + `/team-chat` (markdown/code, documents download-only) + registration types. |
| **It.93o-8** | External is a named create type; one-time invite (`data/registration-invites/`); superadmin confirms; no auto-team on approve. |
| **Follow-ups** | Users tabs; comment **Approve**; Newsletter / Backups / Firewall `AdminTabs`; backup list sort; desk kind + priority labels. |
| **It.96** | Document library: text edit, PDF sandbox preview, bulk ZIP, `[document-link]`. |

Spec: [ITERATION_93.md](ITERATION_93.md) · [ITERATION_96.md](ITERATION_96.md).

---

## Remaining fix from yesterday (beta.85 CI)

GitHub Actions stayed red on the **beta.85** commit. Local isolation was green. The blockers were incomplete tests / policy, not `.github/workflows/ci.yml`.

| Job | Failure | Fix in this tag |
|-----|---------|-----------------|
| Frontend `npm test` | `MediaManager.test.tsx` — mock of `../../api/media` missing `isTextEditableMedia` | Partial mock via `importOriginal`; real helpers stay. |
| Backend PHPUnit | `UploadSecurityValidator` — “Prípona súboru nie je v povolenom zozname” on PNG | Merge media-MIME-derived extensions into the legacy allow-list. |
| Backend PHPUnit | `Save upload rejects unsupported mime type` | Validate declared MIME after filename coalesce; fixture `clip.mp4` + `video/mp4` (not `notes.txt` + octet-stream). |
| Backend PHPUnit | Release webhook expected 200, got **503** | Test fixture now sets `githubToken` (CI has no Git SSH). Controller still returns the service status. |
| Backend PHPUnit | Shortcode catalog 17 vs 18/20 | Bundled catalog is **20** (`staff-card`, `staff-team`, `document-link`); merge by key. |

`ECONNRESET` noise in frontend logs is secondary and not the blocking failure.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.86 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.86`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.86 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Users → tabs Users / New user / One-time registration; invite link creates inactive USER.
- [ ] Contact subject routing + first staff reply claims the thread; visitor gets desk mail from `@site-domain`.
- [ ] Desk bubble off → in-page comment/message composer; notification opens `#comment-` / `#message-`.
- [ ] External team card color + `/team-chat` documents download-only.
- [ ] Media: upload PNG; reject `shell.php.png`; reject video when video MIME is not allowed; edit `notes.txt`.
- [ ] Settings → System update: published GitHub release webhook still 401 without signature; 200 queued when secret + deploy stack + token are set.

See [TESTING.md](developer/TESTING.md).
