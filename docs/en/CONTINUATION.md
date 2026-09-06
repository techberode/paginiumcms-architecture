# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** September 6, 2026 · `v2.1.0-beta.66`  
> **Active phase:** Stabilization — It.25 M1+ shipped; target first stable tag **September 2026**

This document replaces the old chronological “log of everything.” Historical detail remains in [`CHANGELOG.md`](../../CHANGELOG.md), [`ISSUES.md`](ISSUES.md), and individual `ITERATION_*.md` files.

---

## 1. In one sentence

PaginiumCMS is a **No-SQL Hybrid Headless Content Engine**: the React/Vite admin and public site communicate through a Slim REST API and PHP Core, while JSON/Markdown/YAML files remain the mandatory source of truth.

---

## 2. Current state (September 2026)

| Area | Status |
|------|--------|
| Latest release | ✅ `v2.1.0-beta.66` — analytics retention, bots, geo, trends |
| It.25 setup wizard | ✅ basic (`beta.62`) + M1+ preflight/infra (`beta.65`/`beta.66`) |
| It.25 update UX | ✅ dashboard banner + deploy blockers (`beta.64`) |
| Stabilization M1–M5 smoke | 🟡 M1/M5 user-confirmed OK; M2–M4 pending |
| First stable tag target | 🟡 `v2.2.0` — September 2026 when §5 exit criteria met |
| It.83+ / new modules | 🔒 frozen until stable (see [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md)) |

---

## 3. Setup wizard (It.25) — current contract

| Step | UI | Backend |
|------|-----|---------|
| Server | Preflight panel, refresh, block on hard failures | `GET /api/setup/preflight` |
| Administrator | First SUPER_ADMIN | — |
| Site | Name + locale | — |
| Infrastructure | `backendPort`, `storageDriver` | saved on `POST /api/setup/complete` |
| Finish | Redirect → `/login` (no auto-login) | `general.installed = true` |

**Security:** preflight is read-only GET; install hints are hardcoded; **no auto-install from web** ([ISS-162](ISSUES.md#iss-162)).

**CLI path:** `scripts/first-run.sh` remains for maintainers and advanced installs.

---

## 4. Stabilization exit criteria (summary)

See [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) §5:

1. It.25 R1–R6 ✅ (including M1+ preflight).
2. Checklist S1–S28 ≥ 90%.
3. Backup restore drill recorded.
4. `./scripts/iteration-gate.sh` green at tag time.
5. Maintainer smoke M1–M5 before `v2.2.0`.

---

## 5. Key commands

```bash
./scripts/iteration-gate.sh
./scripts/smoke-it25.sh
curl -s http://127.0.0.1:8080/api/setup/preflight | jq .
```

---

## 6. Documentation map (It.25 / setup)

| Doc | Content |
|-----|---------|
| [ITERATION_25.md](ITERATION_25.md) | Delivery record + wizard steps |
| [RELEASE_2_1_0_BETA_65.md](RELEASE_2_1_0_BETA_65.md) | M1+ release note |
| [INSTALLATION.md](user/INSTALLATION.md) §7 | User install + wizard |
| [FIRST_STEPS.md](user/FIRST_STEPS.md) | Post-install checklist |
| [LOCAL_SETUP.md](developer/LOCAL_SETUP.md) §4.1 | Dev browser setup |
| [ISSUES.md](ISSUES.md#iss-162) | Preflight incident record |

---

## 7. Next likely work (not started here)

- M2–M4 stabilization smoke (backup drill, publish flow)
- `v2.2.0` stable tag when §5 complete
- Post-stable: It.83 theme runtime (see [ITERATION_83.md](ITERATION_83.md))

---

## 8. Historical note

Older checkpoints in this file referenced `v2.1.0-beta.23` (August 2026). The canonical “latest” is always the newest section in [CHANGELOG.md](../../CHANGELOG.md).
