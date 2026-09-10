# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** September 9, 2026 · `v2.1.0-beta.68`  
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
| Latest tag | ✅ `v2.1.0-beta.68` — It.87 planner, ISS-169 list pagination, ISS-168 landing hero |
| Unreleased (this tree) | **88a–88d** Theme Studio shell + validate + sandboxed preview (normalize/save later) |
| It.25 setup wizard | ✅ basic + M1+ (`beta.62`–`beta.66`) |
| It.83 theme runtime | ✅ shipped `beta.59` (`terminal-breach`, `clean-journal`) |
| It.84 / It.86 | ✅ shipped |
| First stable tag | ⏸️ **not a goal** — continue `v2.1.0-beta.*` (or later betas) as features land |

---

## 4. Implementation queue (planned iterations only)

Do **not** invent new iteration numbers. Finish specs that already exist.

| Order | Item | Why this order |
|------:|------|----------------|
| 1 | **It.88** Theme Studio | Monaco + policy + preview + thumbnail + normalize — [ITERATION_88.md](ITERATION_88.md); JS tab uses 87k–m |
| 2 | **It.78** Unified upload security | Gate before video / new MIME |
| 3 | **It.79** DAM video | Depends on It.78 |
| 4 | **It.72** S3 / remote media remainder | Local driver already shipped |
| 5 | **It.58f / 58g** layout builder remainder | Page blocks (not theme studio) |
| 6 | **It.70** GitHub API publisher UI | Local Git already shipped |
| 7 | **It.76 / 77** translation providers | After It.73 (shipped) |
| 8 | **It.75** CMS-aware AI agent | Last: proposals only, human apply |
| 9 | **It.48** static / Jamstack output | Align with It.70; do not fork publish pipelines |

**Optional / later:** It.82d Origin host metrics.

**Next slice to open:** **88c** — normalize from paste/ZIP-of-files (`POST /api/admin/themes/normalize`; report + rewritten buffers).

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

---

## 7. Documentation map

| Doc | Content |
|-----|---------|
| [ITERATION_88.md](ITERATION_88.md) | **Next** — Theme Studio |
| [ITERATION_87.md](ITERATION_87.md) | Project planner + UX remainder (shipped) |
| [ITERATION_78.md](ITERATION_78.md) / [ITERATION_79.md](ITERATION_79.md) | Upload policy → video |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Full remaining scope |
| [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) | Closed historical freeze |
| [ISSUES.md](ISSUES.md#iss-169) | Admin list pagination (ISS-169); landing SEO hero (ISS-168) |

---

## 8. Historical note

Older checkpoints referenced `v2.1.0-beta.23` and a September `v2.2.0` stable tag. The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
