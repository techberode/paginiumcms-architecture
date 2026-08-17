# Stabilization phase — freeze expansion, harden what exists

> **Decision:** August 17, 2026  
> **Status:** 🔒 **Active** — no new iteration scope until exit criteria in §5 are met  
> **First stable release target:** **September 2026** — tag only when §5 exit criteria are met (including **It.25**)  
> **Supersedes for planning:** open “recommended next slice” rows in [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) until this phase closes

---

## 1. Why

PaginiumCMS has outgrown “early beta” in code volume but not yet in **first-run delight** and **operational confidence**. Further feature expansion increases surface area without improving the goal:

> A newcomer tries the CMS once and thinks: *“This is exactly the CMS I want to use.”*

This phase **freezes new capabilities** and invests in **stability, polish, and basic completion** of already-planned foundations.

Aligned with [PHILOSOPHY.md](PHILOSOPHY.md): thin core, optional hybrid layers, open source, no feature bloat.

---

## 2. Rules during stabilization

| Rule | Meaning |
|------|---------|
| **No new iterations** | Do not open It.78+, It.58f/g, It.75–77, S3 media, DAM video, AI agent — **except It.25** (stable-release blocker) |
| **It.25 is mandatory** | First stable tag **must not** ship without It.25 basic phase complete (§5.1) |
| **Hotfixes only** | Security, data loss, deploy breakage, gate regressions |
| **Basic phase = “works reliably”** | Not “full vision” — document deferred remainder explicitly |
| **Profile over feature** | Prefer [deployment profiles](architecture/DEPLOYMENT_MODES.md) wiring over new admin modules |
| **Maintainer tools stay gated** | Origin Panel, heavy ops — env-gated; not first-run UX |
| **Gate before tag** | `./scripts/iteration-gate.sh` + `./scripts/run-all-tests.zsh` before any release tag |

---

## 3. Freeze list (explicitly paused)

| Item | Reason |
|------|--------|
| **It.78** unified upload security engine | New surface; stabilize uploads on current validator first |
| **It.79** DAM video | Depends on It.78 |
| **It.58f** outline / region DnD layout builder | Large UX scope; shortcodes + templates sufficient for basic |
| **It.58g** per-shortcode compile/cache assets | Defer until 58f or clear asset contract |
| **It.75** AI agent | Optional; high risk |
| **It.76/77** translation providers | Optional |
| **It.72** S3 / remote media driver | Local driver MVP is enough for stabilization |
| **It.70** GitHub API publisher UI | Local Git foundation shipped |
| **It.82d** Origin host metrics | Maintainer optional |
| **It.83** theme runtime + Terminal Breach | Post-stable slice; design in [ITERATION_83](ITERATION_83.md) |
| **New admin modules** | Unless hotfix for shipped feature |

Candidates in backlog §4 (`Scoped FileManager`, inline editing, etc.) remain **unnamed icebox** — no numbers recycled.

**Not frozen — required for first stable release:** [It.25](ITERATION_25.md) setup wizard and user-facing update UX (§5.1). `first-run.sh` remains the maintainer/CLI path; It.25 is the **product** onboarding layer.

---

## 4. Stabilization checklist (ship-quality for what exists)

Work until each row is ✅ with smoke evidence (manual or automated).

### 4.1 Onboarding and deploy

| # | Area | Basic done means |
|---|------|------------------|
| S1 | **First-run** | `scripts/first-run.sh` → admin login → one published page on clean install |
| S2 | **Deploy docs** | [LOCAL_SETUP.md](developer/LOCAL_SETUP.md) + production deploy path match real `.env` (root wins) |
| S3 | **Health** | `/api/health` reports version; deploy/update scripts don’t clobber content |
| S4 | **Demo vs prod** | `DEMO_MODE` / `ORIGIN_PANEL` documented; never copied blindly to production |

### 4.2 Core content path

| # | Area | Basic done means |
|---|------|------------------|
| S5 | **Pages & articles** | Create, edit, publish, trash, restore; slug/locale without clobber (ISS-149/154 class fixed) |
| S6 | **Editor** | Markdown + Tiptap profiles; preview matches public render |
| S7 | **Multi-locale (It.73)** | SK/EN switch; one page per locale without silent data loss |
| S8 | **Index/cache** | `content:diagnose --fix` recovers orphans; cache invalidation on publish |
| S9 | **Locks** | Concurrent edit fails gracefully; no stuck global lock |

### 4.3 Layout & presentation (It.58 basic)

| # | Area | Basic done means |
|---|------|------------------|
| S10 | **Layout templates** | All five templates render; `layoutTemplate` in front matter respected |
| S11 | **Shortcodes** | Bundled catalog seeds; admin CRUD + preview; expand + sanitize on public site |
| S12 | **pgLayout.css** | Production build includes utilities (ISS-143); 3–5 polished blocks (hero, alert, grid, **coming-soon**) |
| S13 | **Snippets (It.81f)** | CRUD + insert + expand in body |
| S14 | **Themes / appearance** | Color scheme + light/dark applies on public site |

**Known limitation (accepted in stabilization):** `two-column` sidebar is a placeholder slot — document shortcode-in-body workaround until It.58f.

### 4.4 Platform services

| # | Area | Basic done means |
|---|------|------------------|
| S15 | **Auth** | Login, 2FA, lockout recovery; CSRF on mutations |
| S16 | **Media (local)** | Upload, registry, public URL; no path traversal |
| S17 | **Comments / contact** | Submit, moderation baseline, spam heuristics |
| S18 | **Newsletter** | Subscribe + admin list (master switch respected) |
| S19 | **Backup** | Create + download; restore procedure documented and drilled once |
| S20 | **Scheduler** | Scheduled publish fires; job outcomes visible |

### 4.5 Hybrid foundation (basic tier only)

| # | Area | Basic done means |
|---|------|------------------|
| S21 | **Classic profile** | Default install = Mode A in [DEPLOYMENT_MODES.md](architecture/DEPLOYMENT_MODES.md) — no Redis required |
| S22 | **Cache driver** | File/memory auto works; Redis optional documented, not required |
| S23 | **Performance Guard** | Runs without breaking requests; `suggest` mode default |
| S24 | **Git publish (It.70)** | Optional off by default; manual `git:publish` documented |

No new drivers during stabilization — **document** Corporate/News profiles as settings recipes, not new code paths.

### 4.6 Quality and hygiene

| # | Area | Basic done means |
|---|------|------------------|
| S25 | **Iteration gate** | Green on main before tag |
| S26 | **Dev hygiene** | Prefix-only `dev:hygiene` / `run-all-tests.zsh` cleanup documented |
| S27 | **ISSUES triage** | No open critical (🔴) security or data-loss issues |
| S28 | **SK/EN docs** | User paths (install, first steps, backup) accurate in both locales |

---

## 5. Exit criteria — first stable release (September 2026 target)

**Hard rule:** the first **stable/production** tag **must not ship without It.25** at basic phase (§5.1). Everything else may be ≥90%; It.25 is **non-negotiable**.

### 5.1 It.25 — mandatory (basic phase)

See [ITERATION_25.md](ITERATION_25.md) and backlog §3. **Done** means:

| # | Deliverable | Basic done means |
|---|-------------|------------------|
| R1 | **Setup wizard** | `/setup` (or equivalent) only when not installed; steps: admin, site name/locale, completion writes settings + `installed: true`; redirect to dashboard |
| R2 | **No CLI required for first use** | Fresh install reachable via browser without reading shell docs (CLI path documented as optional/advanced) |
| R3 | **Update UX** | SUPER_ADMIN dashboard: “Update available” + explicit “Update now” using existing It.63 engine; backup prompt before apply; hidden in `DEMO_MODE` |
| R4 | **Safety** | SUPER_ADMIN + 2FA where enabled; CSRF; no arbitrary shell; secrets encrypted |
| R5 | **Docs** | [INSTALLATION.md](user/INSTALLATION.md) + [FIRST_STEPS.md](user/FIRST_STEPS.md) SK/EN describe wizard **and** `first-run.sh` fallback |
| R6 | **Tests** | Smoke: clean install via wizard; upgrade from previous beta tag without content loss under `storage/app/content/` |

Stretch goals (package updater without Git, stock-image seed) **do not** block stable release.

### 5.2 Stabilization checklist

1. **S1–S28** ≥ 90% ✅ (≤3 items explicitly deferred with ISS/backlog note).
2. **It.25** R1–R6 ✅ (§5.1 — **required**, not optional).
3. **Gate green** on `main` at tag time; prefer no regressions between August–September stabilization tags.
4. **Backup restore drill** completed once and recorded in [CHECKLIST.md](CHECKLIST.md).
5. **Release tag** e.g. `v2.2.0` (first stable) with CHANGELOG + “Classic profile production-ready” notes.

Until §5.1–5.2 are met: **no stable tag** — beta patches only (`v2.1.0-beta.*`).

### 5.3 Maintainer release smoke (~30 min, before stable tag)

Run once before September stable tag (you do not need to test the whole CMS manually):

| # | Smoke step | Pass |
|---|------------|------|
| M1 | **It.25 wizard** on clean instance → first admin → site settings → dashboard | ☐ |
| M2 | Create and **publish** one page; open on **public** URL | ☐ |
| M3 | Insert one **shortcode** (e.g. `alert-box` or `landing-hero`); public render matches preview | ☐ |
| M4 | **Backup** create + download from admin | ☐ |
| M5 | **Update** banner visible when update available (or documented N/A for non-Git install); no CTA in demo | ☐ |

Automated coverage: `./scripts/iteration-gate.sh` + `./scripts/run-all-tests.zsh` on the same commit as the tag.

---

## 6. Exit criteria (when general unfreeze is allowed)

After the **first stable release**, general feature unfreeze (It.78+, 58f, …) requires:

1. Stable tag shipped per §5.
2. **Gate green** for 4 consecutive weeks on `main` (or no regressions between tags).
3. **Three publish flows** by non-maintainers **or** recorded dry-run scripts (if solo).

Until first stable: **no new iteration numbers** except **It.25**, only stabilization fixes and docs.

---

## 7. Allowed work (narrow)

| Allowed | Examples |
|---------|----------|
| **It.25** | Setup wizard, dashboard update UX (§5.1) |
| Bug fixes | Locale clobber, CSP, preview mismatch, deploy health |
| Polish | Default theme, shortcode CSS, i18n gaps, error messages |
| Docs | STABILIZATION, LOCAL_SETUP, deploy runbooks |
| Tests | Regressions for S5–S9, It.25 smoke, shortcode policy |
| Basic completion | Seed `coming-soon` shortcode + pgLayout block; deployment profile doc as settings preset |
| **It.83 prep (docs/CSS only)** | [ITERATION_83](ITERATION_83.md) spec; S12 cyber-styled `pg-*` utilities; optional `terminal-breach` **color scheme tokens** in Settings (not theme activate) |

| Not allowed | Examples |
|-------------|----------|
| New modules | Video, AI, outline builder, **It.83 theme activate/runtime** |
| Schema breaks | New required settings without defaults |
| Maintainer-only expansion | Origin 82d, host agent |

---

## 8. Suggested release rhythm during stabilization

```text
fix / It.25 slice → gate → run-all-tests → CHANGELOG → beta tag (Aug)
repeat until §5.1 R1–R6 + §5.2 satisfied
September: stable tag v2.2.0 (or agreed stable semver) after M1–M5 smoke
```

Target: **beta patches every 2–4 weeks** in August; **one stable tag in September** when It.25 + smoke pass — no fixed day; quality over calendar pressure.

---

## 9. Related documents

| Doc | Role |
|-----|------|
| [PHILOSOPHY.md](PHILOSOPHY.md) | Why we avoid bloat |
| [DEPLOYMENT_MODES.md](architecture/DEPLOYMENT_MODES.md) | Profile model (documentation-first during freeze) |
| [HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) | Long-term direction — not active build list |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | Frozen items marked ⏸️ stabilization |
| [ITERATION_25.md](ITERATION_25.md) | **Stable-release blocker** — setup wizard + update UX |
| [ITERATION_83.md](ITERATION_83.md) | Post-stable theme runtime + Terminal Breach (design only during freeze) |
| [CHECKLIST.md](CHECKLIST.md) | Human release gate before tag |

---

## 10. Current interpretation

**Stabilization is the product strategy.** The first stable release is **September 2026** (target, not deadline-at-all-costs). **It.25 is the single mandatory feature slice** before that tag; Hybrid Engine remainder stays documented, not built. Success = newcomer completes wizard and thinks *“This is exactly the CMS I want to use”* — not iteration count.
