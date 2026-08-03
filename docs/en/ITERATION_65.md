---
title: Iteration 65 – Feature gallery
description: Flat-file admin screenshot gallery with public grid/slider/modal UX and a layout-block contract.
icon: material/history
---

# Iteration 65 – Feature gallery

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Phases 1–3 complete; operations seeding remained |
| Release / period | 2.1.0-beta.21 + neskoršia Phase 3 na main |
| Record type | historical module delivery record |

## Goal

Deliver a dedicated Gallery module for PaginiumCMS screenshots with titles, descriptions, tags, ordering, and publication on home or a dedicated `/features` route.

## Scope and outcome

The flat-file model used `data/gallery/index.json` and `items/{id}.json`, while Media remained the binary source. Admin `/gallery` provided CRUD, reorder, media picker, settings, and live preview; public API returned published items only.

Phase 1 shipped grid and modal, Phase 2 added slider/hero-strip, autoplay, effect presets, tags, and a dynamic single-segment route, and Phase 3 added deep links, Ken Burns, export/import, and the `featureGallery` contract for It.58. Implementation selected CSS scroll-snap without a new carousel dependency.

## Architecture and security boundaries

Mutations require `gallery:manage`, public reads filter by status, captions are sanitized, and media paths pass the existing storage allow-list. The modal needs focus trapping, ESC, keyboard navigation, and reduced-motion behavior. JSON import must not import binaries or arbitrary paths.

## Verification and related records

Phases 1–2 are tied to [v2.1.0-beta.21](../../CHANGELOG.md#release-2-1-0-beta-21); the source says Phase 3 was “on main (next release)”, so this document does not prove its first tag. An operations task remained to seed 3–5 screenshots on prod/demo.

## Current interpretation

It.65 is a standalone module and SSOT. The It.58 `featureGallery` block may only render the same public API/component; it must not create a second gallery store.
