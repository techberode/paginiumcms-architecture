---
title: Iteration 25 – Setup Wizard and User-Facing Updates before 1.0
description: Browser-first onboarding and dashboard update UX — basic phase delivered in v2.1.0-beta.62
icon: material/check-circle
---

# Iteration 25 – Setup Wizard and User-Facing Updates before 1.0

| Field | Value |
|---|---|
| Status | ✅ **Basic phase shipped** (`v2.1.0-beta.62`, 2026-09-03) |
| Release / period | pre-stable (September 2026 target) |
| Record type | delivery record + deferred stretch scope |

## Product goal

First-run onboarding in the browser and a dashboard “Update available / Update now” experience for SUPER_ADMIN, built on the It.63 deployment engine.

## Delivered (basic phase — STABILIZATION §5.1)

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

## Deferred (stretch / post-basic)

| Item | Note |
|---|---|
| Optional stock-image seed in wizard | Out of basic phase |
| Git/package detection + deploy checklist step | Maintainer docs cover this |
| Full rollback UI | Backup prompt only in basic phase |
| Package updater without git | Stretch goal per original plan |

## Security contract (unchanged)

SUPER_ADMIN plus 2FA where enabled, CSRF on mutating routes, encrypted secrets, no arbitrary shell, update UI hidden in demo mode.

## Links

- [RELEASE_2_1_0_BETA_62.md](RELEASE_2_1_0_BETA_62.md)
- [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) §5.1
- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-62)
