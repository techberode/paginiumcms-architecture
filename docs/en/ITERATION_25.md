---
title: Iteration 25 – Setup Wizard and User-Facing Updates before 1.0
description: Historical planned design for a first-run wizard and one-click update UX over the existing deployment engine
icon: material/history
---

# Iteration 25 – Setup Wizard and User-Facing Updates before 1.0

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ⏳ Planned; delivery is not confirmed by the source |
| Release / period | post-beta, pred 1.0 GA |
| Record type | historical product plan |

## Product goal

Add first-run onboarding and a dashboard “Update available / Update now” experience for SUPER_ADMIN. The source says the technical deployment engine shipped in It.63 (`v2.1.0-beta.18`), while It.25 was meant to turn it into a safe user-facing flow before stable 1.0.

## Planned setup wizard

| Step | Content |
|---|---|
| 1 | create or confirm the first SUPER_ADMIN |
| 2 | site name, locale, and optional stock-image topic |
| 3 | detect git/package installation, GitHub repo/token, and deployment-permissions checklist |
| 4 | optional stock-image seed |
| completion | atomic settings write plus `installed: true`, then dashboard redirect |

## Planned update UX

A dashboard banner, explicit update confirmation, reuse of the whitelisted deployment job, human-readable progress, a backup prompt, and clear behavior for demo or non-git installations. Unattended updates without confirmation remained out of scope.

## Security contract

SUPER_ADMIN plus 2FA, CSRF, encrypted secrets, no arbitrary shell, and hidden update UI in demo mode. A package-based updater without git was only a stretch goal.

## Current status and acceptance

The supplied materials do not prove that `/setup`, `POST /api/setup/complete`, or the dashboard one-click update flow were completed. This document therefore remains a plan. Acceptance required a fresh-install wizard, preservation of `storage/app/content/`, no update CTA in demo mode, and updated Installation/Admin Guide documentation.

