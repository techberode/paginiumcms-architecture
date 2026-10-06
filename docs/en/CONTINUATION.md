# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** October 6, 2026 · `main` @ **`v2.1.0-beta.97`** (Experience Phase C + B3)  
> **Active phase:** **Full planned-iteration development** — stabilization freeze lifted  
> **Next planned slice:** **It.98** workspace+CLI — [ITERATION_98.md](ITERATION_98.md) · optional **B3** / 58f-i-d — [PUBLIC_EXPERIENCE_ROADMAP.md](architecture/PUBLIC_EXPERIENCE_ROADMAP.md) · **It.97** **deferred**

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

## 3. Current state (October 2026)

| Area | Status |
|------|--------|
| Latest tag | ✅ **`v2.1.0-beta.97`** — reference landing seeds + reading progress + view transitions — [RELEASE_2_1_0_BETA_97.md](RELEASE_2_1_0_BETA_97.md) (prior: [beta.96](RELEASE_2_1_0_BETA_96.md)) |
| Unreleased | **It.82d** host metrics · optional 58f-i-d crossfade · in-CMS docs portal proposal · Experience **B3** |
| Origin Panel | Refresh “today” snapshot after deploy; canonical version = **`AppVersion::current()`** / latest git tag |
| Previous tag | `v2.1.0-beta.95` — `[media-gallery]` · unified lightbox · feature gallery `?slide=` · shortcode cookbook |
| It.48 | ✅ compile + public HTML serve in `beta.89` |
| It.92 | ✅ SQLite **derived catalog index only** (`beta.83`–`85`) — not SSOT; Classic default stays `content.json` |
| It.69 | ✅ file/memory/auto + optional **Redis** derived cache + HTTP validators (never SSOT; shared hosting OK without Redis) |
| It.93o | ✅ **93o-2–8** in `beta.86` — desk/staff/external team |
| It.96 | ✅ document library in `beta.86` |
| First stable tag | ⏸️ **not a goal** — continue `v2.1.0-beta.*` as features land |

**Derived layers (do not invent a database):**

- **SQLite** (It.92) — optional query index for lists/search under load. Rebuildable. Files stay SSOT.
- **Redis** (It.69) — optional **derived cache only**. `auto` uses Redis when `REDIS_HOST` connects; otherwise memory + file. Setup wizard reports extension/broker as **info**, not blockers.

---

## 4. Implementation queue (planned iterations only)

Do **not** invent new iteration numbers. Finish specs that already exist.

| Order | Item | Why this order |
|------:|------|----------------|
| 0 | ✅ **Islands A–F** + **58f-i-a/b** — registry, gallery v2, `section-band`, carousel, effects, admin preview parity | See [REACT_SHORTCODE_ISLANDS.md](architecture/REACT_SHORTCODE_ISLANDS.md) |
| 1 | ✅ **58f-i-i…k** gallery + lightbox (`beta.95`) | [GALLERY_LIGHTBOX_PLANNED.md](architecture/GALLERY_LIGHTBOX_PLANNED.md) |
| 2 | ✅ **Experience B1** — `[visual-frame]` motion presets | [PUBLIC_EXPERIENCE_ROADMAP.md](architecture/PUBLIC_EXPERIENCE_ROADMAP.md) |
| 3 | ✅ **Experience B2** — section-band reveal/hover v3 (`beta.96`) | same |
| 4 | ✅ **Experience C** — reference landing seeds + outline starters | [LANDING_PAGE.md](user/LANDING_PAGE.md) |
| 5 | **It.58f-i** remainder (58f-i-d crossfade, before-after, callout theme) | [PAGE_FIELD_COMPOSER_PLANNED.md](architecture/PAGE_FIELD_COMPOSER_PLANNED.md) |
| 5b | ✅ **It.99** Upload polyglot hardening (marker scan, secure filenames, opt-in re-encode) | [ISS-195](../ISSUES.md#iss-195) · [ITERATION_99.md](ITERATION_99.md) |
| 6 | **It.82d** Origin host metrics | Optional maintainer hook |
| 7 | **It.98** Multi-site workspace + CLI content ops (WP-style, phased) | [ITERATION_98.md](ITERATION_98.md) |
| 8 | **Growth proposals** (A/B, related posts, TOC, cross-post, visual B3) | [PRODUCT_GROWTH_PROPOSALS.md](architecture/PRODUCT_GROWTH_PROPOSALS.md) |

Isolated-origin widgets are **not** queued (cancelled iteration; archive only: [ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md)).

Admin deep-links: `/settings?group=engine` (legacy `/settings/engine` redirects). Admin SPA paths stay first-segment (`/mail`, `/kanban`, `/team-chat`; legacy `/platform/*` redirects).

---

## 5. Setup wizard (It.25) — current contract

| Step | UI | Backend |
|------|-----|---------|
| Server | Preflight panel, refresh, block on hard failures; **optional Redis** shown as info/warn (not blocking) | `GET /api/setup/preflight` |
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
| [RELEASE_2_1_0_BETA_96.md](RELEASE_2_1_0_BETA_96.md) | Latest release — Experience B1–B2 motion |
| [RELEASE_2_1_0_BETA_95.md](RELEASE_2_1_0_BETA_95.md) | Gallery/lightbox completion |
| [SHORTCODE_COOKBOOK.md](user/SHORTCODE_COOKBOOK.md) | Operator examples for all bundled shortcodes |
| [DOCS_PORTAL_PLANNED.md](developer/DOCS_PORTAL_PLANNED.md) | Future in-CMS Markdown docs module |
| [ITERATION_48.md](ITERATION_48.md) | Static compile + `/static-html` serve |
| [ITERATION_58f.md](ITERATION_58f.md) | Visual blocks; **58f-i** gallery/motion shipped through **beta.96** |
| [ITERATION_69.md](ITERATION_69.md) | Unified cache; optional Redis derived layer |
| [ITERATION_92.md](ITERATION_92.md) | SQLite derived query index |
| [ITERATION_70.md](ITERATION_70.md) | Git publish (`local` + `github_api`) |
| [ITERATION_75.md](ITERATION_75.md) | CMS AI assistant (`beta.88`) |
| [ITERATION_93.md](ITERATION_93.md) | Admin chrome + desk; **93l-2** shipped |
| [ITERATION_95.md](ITERATION_95.md) | Sandpack playground **95a/c/b/d** shipped |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Full remaining scope |
| [ISSUES.md](ISSUES.md#iss-173) | ISS-173 gitleaks · ISS-174 desk role |

---

## 8. Historical note

Older checkpoints referenced `v2.1.0-beta.23` and a September `v2.2.0` stable tag. The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
