# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** September 20, 2026 · `v2.1.0-beta.89`  
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
| Latest tag | ✅ `v2.1.0-beta.89` — It.48 static compile + `/static-html` · ISS-173 gitleaks · ISS-174 desk role · AppVersion floor |
| Unreleased | Production deploy of `v2.1.0-beta.89` |
| Origin Panel | Today snapshot as of 2026-09-20 · latest tag `2.1.0-beta.89` · remaining: 58f-h, It.95, 93l-2, It.69 Redis cache driver |
| Previous tag | `v2.1.0-beta.88` — It.75 CMS AI assistant · contact E.164 + SMTP reply · discussion ratings · deploy-key remount |
| It.48 | ✅ compile + public HTML serve in `beta.89` |
| It.92 | ✅ SQLite **derived catalog index only** (`beta.83`–`85`) — not SSOT; Classic default stays `content.json` |
| It.69 | ✅ file/memory/auto cache + HTTP validators; **Redis cache driver still deferred** (never SSOT) |
| It.93o | ✅ **93o-2–8** in `beta.86` — desk/staff/external team |
| It.96 | ✅ document library in `beta.86` |
| First stable tag | ⏸️ **not a goal** — continue `v2.1.0-beta.*` as features land |

**Derived layers (do not invent a database):**

- **SQLite** (It.92) — optional query index for lists/search under load. Rebuildable. Files stay SSOT.
- **Redis** (It.69 remainder) — planned **cache only**. `engine.cacheDriver=redis` currently falls back to `auto`. Guard never enables it.

---

## 4. Implementation queue (planned iterations only)

Do **not** invent new iteration numbers. Finish specs that already exist.

| Order | Item | Why this order |
|------:|------|----------------|
| 1 | **58f-h** visual block canvas | Next numbered remainder of [ITERATION_58f.md](ITERATION_58f.md) |
| 2 | **It.95** Sandpack playground | Spec exists; private component registry |
| 3 | **93l-2** canned replies / SLA notes | Remainder of It.93 — no new number |
| — | **It.69 Redis driver** | Optional cache only; Classic must keep working without Redis |
| — | **It.82d** Origin host metrics | Optional maintainer hook |

Isolated-origin widgets are **not** queued (cancelled iteration; archive only: [ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md)).

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
| [RELEASE_2_1_0_BETA_89.md](RELEASE_2_1_0_BETA_89.md) | Latest release — It.48 · ISS-173/174 |
| [ITERATION_48.md](ITERATION_48.md) | Static compile + `/static-html` serve |
| [ITERATION_58f.md](ITERATION_58f.md) | Visual blocks; **58f-h** canvas remaining |
| [ITERATION_69.md](ITERATION_69.md) | Unified cache; Redis driver deferred |
| [ITERATION_92.md](ITERATION_92.md) | SQLite derived query index |
| [ITERATION_70.md](ITERATION_70.md) | Git publish (`local` + `github_api`) |
| [ITERATION_75.md](ITERATION_75.md) | CMS AI assistant (`beta.88`) |
| [ITERATION_93.md](ITERATION_93.md) | Admin chrome + desk; **93l-2** later |
| [ITERATION_95.md](ITERATION_95.md) | Sandpack playground (planned) |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Full remaining scope |
| [ISSUES.md](ISSUES.md#iss-173) | ISS-173 gitleaks · ISS-174 desk role |

---

## 8. Historical note

Older checkpoints referenced `v2.1.0-beta.23` and a September `v2.2.0` stable tag. The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
