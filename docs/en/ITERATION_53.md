---
title: Iteration 53 – Smooth SPA reload and admin navigation
description: React Query caching, skeletons, scroll restoration, and removal of hard reloads.
icon: material/history
---

# Iteration 53 – Smooth SPA reload and admin navigation

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.39 |
| Record type | historical frontend delivery record |

## Goal

Remove unnecessary full-page reloads and blank blocking spinners when navigating between admin modules.

## Scope and outcome

Delivery introduced `QueryClientProvider`, stable query keys, cached dashboard/lists/media/extensions/counts, skeleton components, and admin scroll-container reset. Editor version restore switched to `loadContent()` instead of `window.location.reload()`.

Session refresh remained event-based, public `/login` used React Router, and the debug tracker measured route-transition duration.

## Architecture and security boundaries

Caching must not bypass authorization or retain stale sensitive responses after logout or role changes. Query invalidation must follow mutations and session boundaries; hard redirects must not replace managed auth state.

## Verification and related records

Release: [2.0.39](../../CHANGELOG.md#release-2-0-39). The source links the session and hard-redirect incidents [ISS-025](ISSUES.md#iss-025) and [ISS-033](ISSUES.md#iss-033).

## Current interpretation

It.53 is the frontend baseline for later builders and extensions. Runtime plugin UI is still constrained by Vite build-time loading; SPA caching does not solve that boundary.
