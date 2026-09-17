# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** September 17, 2026 · `v2.1.0-beta.80`  
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
| Latest tag | ✅ `v2.1.0-beta.80` — It.89a plugin capabilities · Docker git version |
| Unreleased (this tree) | — **It.89** plugin SDK · **It.92** SQLite · **It.48** static compile (58g) · **It.94** novice admin UX · **58f-h** block canvas · **It.95** Sandpack playground + private component registry |
| It.25 setup wizard | ✅ basic + M1+ (`beta.62`–`beta.66`) |
| It.83 theme runtime | ✅ shipped `beta.59` (`terminal-breach`, `clean-journal`) |
| It.84 / It.86 | ✅ shipped |
| First stable tag | ⏸️ **not a goal** — continue `v2.1.0-beta.*` (or later betas) as features land |

---

## 4. Implementation queue (planned iterations only)

Do **not** invent new iteration numbers. Finish specs that already exist.

| Order | Item | Why this order |
|------:|------|----------------|
| 1 | **It.89** Plugin capability model | Safe editor-tool plugins (It.90e SDK overlap) |
| 2 | **It.92** SQLite query index + Guard advisor | Derived catalog index; files stay SSOT — [ITERATION_92.md](ITERATION_92.md) |
| 3 | **It.70** GitHub API publisher UI | Local Git already shipped |
| 4 | **It.76 / 77** translation providers | After It.73 (shipped) |
| 5 | **It.75** CMS-aware AI agent | Last: proposals only, human apply |
| 6 | **It.48** static / Jamstack output | Align with It.70; includes **58g** compile/cache |
| — | **58f-h** + **It.94** (94a→94d) | **P1 product** after or parallel to It.89 — [ITERATION_94.md](ITERATION_94.md); canvas is **58f-h**, not 94 |

Shipped ahead of this queue: **It.58f** visual page blocks (**58f-a–g**) — [ITERATION_58f.md](ITERATION_58f.md) in **`beta.79`**. **It.93** admin chrome + daily apps — [ITERATION_93.md](ITERATION_93.md) **`beta.77`–`79`** (93m-5 mail polish in **`beta.79`**).

**Optional / later:** It.82d Origin host metrics. Isolated-origin widgets are **not** queued (cancelled iteration; archive only: [ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md)).

**Active slice:** **It.89b** plugin capability broker. **89a** catalog + import validation shipped in **`v2.1.0-beta.80`**. Fullscreen **editor workspace** and **93m-5** mail shipped in **`v2.1.0-beta.79`**. Admin SPA paths stay first-segment (`/mail`, `/kanban`; legacy `/platform/*` redirects). Settings field i18n: [SETTINGS_I18N.md](SETTINGS_I18N.md).

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
| [ITERATION_78.md](ITERATION_78.md) / [ITERATION_79.md](ITERATION_79.md) | Upload policy + DAM video (shipped `beta.70`–`71`) |
| [ITERATION_90.md](ITERATION_90.md) | Editor Workbench (shipped `beta.72`–`73`) |
| [ITERATION_91.md](ITERATION_91.md) | Trusted HTML & embeds (shipped `beta.75`) |
| [RELEASE_2_1_0_BETA_80.md](RELEASE_2_1_0_BETA_80.md) | Latest release notes |
| [ITERATION_89.md](ITERATION_89.md) | Plugin capability model (**89a** shipped; 89b–e planned) |
| [ITERATION_92.md](ITERATION_92.md) | SQLite query index + Guard advisor (planned) |
| [ITERATION_93.md](ITERATION_93.md) | Admin chrome + teams, support, domain mail, events, time tracker |
| [architecture/ADMIN_DEEP_LINKS.md](architecture/ADMIN_DEEP_LINKS.md) | Admin SPA paths (`/{module}`; `/platform/*` aliases) |
| [architecture/ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md) | Cancelled iteration archive (was first It.93 draft; never implemented) |
| [ITERATION_58.md](ITERATION_58.md) / [ITERATION_58f.md](ITERATION_58f.md) | Layout 58b–e shipped; **58f-a–g shipped**; **58g** compile with It.48 |
| [ITERATION_87.md](ITERATION_87.md) | Project planner + UX remainder (shipped) |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Full remaining scope |
| [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) | Closed historical freeze |
| [ISSUES.md](ISSUES.md#iss-170) | Security audit fixes (ISS-170–172, [Unreleased]); pagination ISS-169; landing SEO ISS-168 |

---

## 8. Historical note

Older checkpoints referenced `v2.1.0-beta.23` and a September `v2.2.0` stable tag. The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
