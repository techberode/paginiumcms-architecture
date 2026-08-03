---
title: Iteration 51 – Live preview, tags, and date labels
description: Fullscreen draft preview, visible tags, and consistent created/updated labels.
icon: material/history
---

# Iteration 51 – Live preview, tags, and date labels

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.32 |
| Record type | historical editor UX record |

## Goal

Let editors preview a full page with Navbar and Footer before publishing, and expose tags and date labels directly in the blog UX.

## Scope and outcome

`SitePreviewModal` supported multiple scales and a preview mode that prevented navigation outside the modal. `ArticleTagsEditor` synchronized `tags[]` with SEO values, while `formatContentDateLabels()` unified created/updated labels on cards and detail pages.

This iteration improved editor UX but did not replace the originally planned shareable draft-token preview.

## Architecture and security boundaries

Preview must use the same renderer, sanitization, and capability rules as public output; it must not have a preview-only bypass. Draft content must not be exposed anonymously without a separate secure token contract.

## Verification and related records

Delivery is recorded in [2.0.32](../../CHANGELOG.md#release-2-0-32). The source identifies frontend tests for `SitePreviewModal` and `contentDates`.

## Current interpretation

It.51 is the completed preview baseline. Later layout and color-scheme preview in It.58 builds on it, while shareable draft URLs remain a separate capability.
