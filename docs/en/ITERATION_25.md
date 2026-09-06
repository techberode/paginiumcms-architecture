---
title: Iteration 25 – Setup Wizard and User-Facing Updates before 1.0
description: Browser-first onboarding and dashboard update UX — basic phase beta.62, M1+ preflight beta.65
icon: material/check-circle
---

# Iteration 25 – Setup Wizard and User-Facing Updates before 1.0

| Field | Value |
|---|---|
| Status | ✅ **Basic phase** (`v2.1.0-beta.62`) + ✅ **M1+ preflight/infra** (`v2.1.0-beta.65`) |
| Release / period | pre-stable (September 2026 target) |
| Record type | delivery record + remaining stretch scope |

## Product goal

First-run onboarding in the browser and a dashboard “Update available / Update now” experience for SUPER_ADMIN, built on the It.63 deployment engine.

## Delivered (basic phase — STABILIZATION §5.1) — `beta.62`

| Step / item | Status |
|---|---|
| `/setup` when not installed | ✅ |
| First SUPER_ADMIN + site name/locale | ✅ |
| `general.installed = true` + dashboard redirect | ✅ |
| Dashboard update banner (SUPER_ADMIN, not demo) | ✅ |
| Backup prompt before deploy | ✅ |
| CSRF-exempt `POST /api/setup/complete` only | ✅ |
| INSTALLATION + FIRST_STEPS SK/EN | ✅ |
| PHPUnit + `scripts/smoke-it25.sh` | ✅ |

## Delivered (M1+ stretch — `beta.65`)

| Step / item | Status |
|---|---|
| **Server preflight step** | ✅ read-only; blocks on hard failures |
| **`GET /api/setup/preflight`** | ✅ PHP ≥8.5, extensions, storage, CLI hints |
| **Install procedure hints** | ✅ Ubuntu/Debian copy-paste commands; **no web auto-install** |
| **Infra step** | ✅ `backendPort`, `media.storageDriver` at setup |
| **Orphan recovery** | ✅ `needsSetup` when zero users (`beta.63`) |
| **Deploy readiness UX** | ✅ dashboard banner + blockers (`beta.64`) |

### Wizard steps (current)

| # | Step | Purpose |
|---|------|---------|
| 1 | **Server** | Preflight checks + install hints |
| 2 | **Administrator** | First SUPER_ADMIN |
| 3 | **Site** | Site name + admin locale |
| 4 | **Infrastructure** | Backend health port, storage driver |
| 5 | **Finish** | Persist settings, redirect to `/login` (explicit sign-in) |

## Still deferred (post-M1+)

| Item | Note |
|---|---|
| Optional stock-image seed in wizard | Stretch |
| Auto-install missing OS packages from web | **Rejected** — security; hints only |
| Full rollback UI | Backup prompt only |
| Package updater without git | Stretch goal |

## Security contract

- SUPER_ADMIN plus 2FA where enabled; CSRF on mutating routes after setup.
- Setup routes under `/api/setup/` are CSRF-exempt for pre-auth bootstrap only.
- **Preflight is GET-only** — no shell, no outbound HTTP, no user-controlled commands.
- Install steps are **hardcoded whitelist** in PHP (Debian/Ubuntu).
- Encrypted secrets; update UI hidden in demo mode; no arbitrary shell from admin UI.

## API summary

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| GET | `/api/setup/status` | none | `needsSetup`, `installed`, `hasUsers` |
| GET | `/api/setup/preflight` | none | Server prerequisite checks |
| POST | `/api/setup/complete` | none | Create admin + settings (once) |

## Links

- [RELEASE_2_1_0_BETA_62.md](RELEASE_2_1_0_BETA_62.md) — basic wizard
- [RELEASE_2_1_0_BETA_65.md](RELEASE_2_1_0_BETA_65.md) — M1+ preflight
- [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) §5.1
- [INSTALLATION.md](user/INSTALLATION.md) §7
- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-65)
