---
title: Iteration 23 – SEO Meta Engine
description: Historical record of server-generated SEO payloads, the public endpoint, and React head-tag management
icon: material/history
---

# Iteration 23 – SEO Meta Engine

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.11 |
| Record type | historical SEO iteration |

## Goal

Generate title, description, canonical, robots, Open Graph, Twitter Card, and JSON-LD for the public React site from content front matter and central SEO settings.

## Backend contract

`SeoMetaBuilder` composes the payload from explicit SEO fields, title, excerpt/body, and settings fallbacks. The public `GET /api/seo/{type}/{slug}` endpoint supports `page|article`; anonymous clients must not receive draft metadata and get `404`.

| Field | Typical source |
|---|---|
| title | `seoTitle` / `metaTitle` or title template |
| description | SEO description, description, excerpt, or body fallback |
| canonical | front matter or site URL + path |
| robots | per-content `noIndex` or default |
| structured data | `WebPage` or `Article` JSON-LD |

## Frontend

`useSeoMeta` fetches the SEO payload for the current document and manages `document.title`, meta tags, canonical link, OG/Twitter fields, and the `#paginium-json-ld` script. Integration lived in `PublicSiteLayout`.

## Later extensions

The global indexing switch arrived in [It.20](ITERATION_20.md). Admin editing of SEO fields, media alt/title, and SEO-health badges followed in [It.27](ITERATION_27.md). Canonical content-field storage is documented in [CONTENT_API.md](architecture/CONTENT_API.md).

## Verification

PHPUnit covered the builder and controller, and Vitest covered the hook. The release is recorded under [CHANGELOG 2.0.11](../../CHANGELOG.md#release-2-0-11).

