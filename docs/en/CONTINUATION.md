# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** September 19, 2026 · `v2.1.0-beta.87`  
> **Active phase:** **Full planned-iteration development** — stabilization freeze lifted

This document replaces the old chronological “log of everything.” Historical detail remains in [`CHANGELOG.md`](../../CHANGELOG.md), [`ISSUES.md`](ISSUES.md), and individual `ITERATION_*.md` files.

---

## 1. In one sentence

PaginiumCMS is a **No-SQL Hybrid Headless Content Engine**: the React/Vite admin and public site communicate through a Slim REST API and PHP Core, while JSON/Markdown/YAML files remain the mandatory source of truth.

---

## 2. Decision — September 9, 2026

The **stabilization freeze is closed**. There is no tester pool and no near-term `v2.2.0` stable tag. The CMS is a **solo-maintainer product**: keep shipping planned iterations, keep the iteration gate, do not block on community smoke or a first-stable ceremony.

Historical freeze record: [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) (superseded).

**Still required on every slice:** `./scripts/iteration-gate.sh` green, security baseline, No-SQL SSOT, SK/EN i18n for user-facing strings, CHANGELOG.

---

## 3. Current state (September 2026)

| Area | Status |
|------|--------|
| Latest tag | ✅ `v2.1.0-beta.87` — It.70 GitHub API publisher · It.76/77 assisted translation |
| Unreleased | (empty feature slice) — next: **It.75** |
| Previous tag | `v2.1.0-beta.86` — It.93o-2–8 desk/staff · It.96 documents · remaining beta.85 CI |
| It.93o | ✅ **93o-2–8** in `beta.86` — social verify, staff cards, subject routing + Messenger, comment desk, reply mail, external team + one-time invite, Users/desk/admin-tab follow-ups |
| It.96 | ✅ document library in `beta.86` |
| It.94 | ✅ novice admin UX |
| It.92 | ✅ SQLite derived query index (`beta.83`–`85`) |
| It.25 setup wizard | ✅ basic + M1+ (`beta.62`–`beta.66`) |
| It.83 theme runtime | ✅ shipped `beta.59` (`terminal-breach`, `clean-journal`) |
| It.84 / It.86 | ✅ shipped |
| First stable tag | ⏸️ **not a goal** — continue `v2.1.0-beta.*` as features land |

---

## 4. Implementation queue (planned iterations only)

Do **not** invent new iteration numbers. Finish specs that already exist.

| Order | Item | Why this order |
|------:|------|----------------|
| 1 | **It.75** CMS-aware AI agent | After translation providers; proposals only, human apply |
| 2 | **It.48** static / Jamstack output | Align with It.70; includes **58g** compile/cache |
| — | **58f-h** | **P1 product** — visual canvas; novice UX shipped in **It.94** ([ITERATION_94.md](ITERATION_94.md)) |

**Optional / later:** It.82d Origin host metrics. Isolated-origin widgets are **not** queued (cancelled iteration; archive only: [ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md)).

**93l-2** (canned replies / SLA notes) stays a listed remainder of It.93 — no new iteration number.

Admin deep-links: `/settings?group=engine` (legacy `/settings/engine` redirects). Admin SPA paths stay first-segment (`/mail`, `/kanban`, `/team-chat`; legacy `/platform/*` redirects).

---

## 5. Setup wizard (It.25) — current contract

| Step | UI | Backend |
|------|-----|---------|
| Server | Preflight panel, refresh, block on hard failures | `GET /api/setup/preflight` |
| Administrator | First SUPER_ADMIN | — |
| Site | Name + locale | — |
| Infrastructure | `backendPort`, `storageDriver` | saved on `POST /api/setup/complete` |
| Finish | Redirect → `/login` (no auto-login) | `general.installed = true` |

**Security:** preflight is read-only GET; **no auto-install from web** ([ISS-162](ISSUES.md#iss-162)). CLI: `scripts/first-run.sh`.

---

## 6. Key commands

```bash
./scripts/iteration-gate.sh
./scripts/smoke-it25.sh
curl -s http://127.0.0.1:8080/api/setup/preflight | jq .
```

Frontend Vite: **`:3025`** (not 3026). White screen on the wrong port is not a CMS bug.

---

## 7. Documentation map

| Doc | Content |
|-----|---------|
| [RELEASE_2_1_0_BETA_87.md](RELEASE_2_1_0_BETA_87.md) | Latest release — It.70 GitHub API · It.76/77 translation |
| [ITERATION_70.md](ITERATION_70.md) | Git publish (`local` + `github_api`) |
| [ITERATION_76.md](ITERATION_76.md) / [ITERATION_77.md](ITERATION_77.md) | Assisted translation (own LibreTranslate or DeepL/Google) |
| [ITERATION_93.md](ITERATION_93.md) | Admin chrome + daily apps; **93o-2–8** desk/staff/external |
| [ITERATION_96.md](ITERATION_96.md) | Document library (shipped `beta.86`) |
| [ITERATION_94.md](ITERATION_94.md) | Novice admin UX |
| [ITERATION_92.md](ITERATION_92.md) | SQLite query index + Guard advisor |
| [ITERATION_78.md](ITERATION_78.md) / [ITERATION_79.md](ITERATION_79.md) | Upload policy + DAM video |
| [ITERATION_90.md](ITERATION_90.md) | Editor Workbench |
| [ITERATION_91.md](ITERATION_91.md) | Trusted HTML & embeds |
| [ITERATION_89.md](ITERATION_89.md) | Plugin capability model (**89a–e**) |
| [architecture/ADMIN_DEEP_LINKS.md](architecture/ADMIN_DEEP_LINKS.md) | Admin SPA paths (`/{module}`; `/platform/*` aliases) |
| [architecture/ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md) | Cancelled iteration archive |
| [ITERATION_58.md](ITERATION_58.md) / [ITERATION_58f.md](ITERATION_58f.md) | Layout 58b–e shipped; **58f-a–g shipped**; **58g** compile with It.48 |
| [ITERATION_87.md](ITERATION_87.md) | Project planner + UX remainder |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Full remaining scope |
| [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) | Closed historical freeze |
| [ISSUES.md](ISSUES.md#iss-170) | Security audit fixes (ISS-170–172); pagination ISS-169; landing SEO ISS-168 |

---

## 8. Historical note

Older checkpoints referenced `v2.1.0-beta.23` and a September `v2.2.0` stable tag. The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
