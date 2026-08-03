---
title: Iteration 30 – Content Admin Polish, Cache and Responsive Lists
description: Historical record of the content-cache fix, dual-mode editor, and shared list toolbar
icon: material/history
---

# Iteration 30 – Content Admin Polish, Cache and Responsive Lists

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented |
| Release / period | 2.0.20 · SEO doplnok 2.0.23 |
| Record type | historical content-admin iteration |

## Goal and priority

Remove the critical condition where cache stored PHP objects and could produce an empty table after refresh, while improving the editor, SEO context, and responsive lists.

## Backend

- cache stores serialized API fields rather than `Content` objects;
- `null` is not stored as a valid cache hit;
- `ChainedDriver::increment()` reads generation from the file layer;
- the index rebuilds when empty while content files exist;
- CLI `content:cache-purge [--reindex]`;
- failed-login email is sent only at lockout, with cooldown and test exclusions.

## Frontend

Markdown and WYSIWYG modes with `contentFormat`, `ContentEditorShell`, SEO panel, media picker for OG/preview images, shared `AdminListToolbar`, localized labels, mobile cards, and settings-driven `itemsPerPage`.

## Operational procedure

The source prescribed a one-time `content:cache-purge --reindex` after deployment. Deleting cache files is a fallback, not the preferred workflow. Reindexing must be safe, reproducible from SSOT, and must not remove authoritative content.

## Verification and current interpretation

The test plan covered create/refresh, version/body loading, editor-mode switching, mobile cards, and CLI purge. Later cache/index rules are consolidated in [STORAGE.md](architecture/STORAGE.md) and [CONTENT_API.md](architecture/CONTENT_API.md). Release: [2.0.20](../../CHANGELOG.md#release-2-0-20).

