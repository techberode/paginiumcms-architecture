---
title: Iteration 26 – Media Preview Lightbox and Binary Hotfix
description: Historical record of Fit/1:1 media previews and the binary-upload corruption fix
icon: material/history
---

# Iteration 26 – Media Preview Lightbox and Binary Hotfix

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete including hotfix |
| Release / period | 2.0.14 |
| Record type | historical media UX and storage fix |

## Goal

Deliver a lightbox with Fit and native 1:1 modes, metadata, and navigation within the current filter, then fix the two root causes of broken previews.

## Frontend scope

`MediaPreviewLightbox.tsx` supported backdrop/X/Escape close, previous/next arrow navigation, natural dimensions, size, and MIME. `MediaManager` opened Fit from the thumbnail and 1:1 from a dedicated action. PDFs and non-image assets opened outside the lightbox.

## 2.0.14 hotfix

| Layer | Fix |
|---|---|
| Binary I/O | `writeBinary()` / `readBinary()` without UTF-8 normalization |
| Validation | extension plus MIME plus magic bytes for JPEG, PNG, GIF, WebP, SVG, and PDF |
| API | authenticated `GET /api/media/file/{path}` and formats endpoint |
| URL | admin same-origin `/api/media/file/...`, public embed `/storage/...` |
| UI | API-driven `accept` and fallback behavior |

## Operational note

The source required deleting and re-uploading media uploaded before 2.0.14 because binary data may have been irreversibly corrupted by normalization. This operation must follow a backup and confirmation that the files are affected legacy uploads.

## Verification and boundaries

Tests covered the lightbox, MediaFormats, repository/controller, and URL helpers. Thumbnail generation, zoom/pan, and broader editor preview remained out of scope. Release: [2.0.14](../../CHANGELOG.md#release-2-0-14).

