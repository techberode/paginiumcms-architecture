---
title: Iteration 27 – Admin View Modes and SEO Metadata Panel
description: Historical record of list/list-preview/grid modes, SEO editing, and health badges
icon: material/history
---

# Iteration 27 – Admin View Modes and SEO Metadata Panel

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; SEO audit endpoint remained deferred |
| Release / period | 2.0.15 · doplnky do 2.0.23 |
| Record type | historical admin UX/SEO iteration |

## Goal

Unify three display modes across Media, Pages, and Articles, and let editors manage SEO fields without manually editing front matter.

## View modes

| Mode | Use |
|---|---|
| `list` | table, sorting, and bulk selection |
| `list-preview` | row with thumbnail or featured image |
| `preview` | card or masonry grid |

`useAdminViewMode` stored a per-module preference in `localStorage`. Server-side user preference synchronization was only an optional future direction.

## SEO workflow

`SeoMetadataPanel`, `MediaMetadataModal`, and `SeoHealthBadge` exposed title, meta description, OG image, robots, canonical, alt text, and related sidecar/front-matter fields. The rule was to reuse `SeoMetaBuilder`, not build a second SEO engine.

Green/yellow/red health state was editor assistance, not a security guarantee or automatic publication gate.

## Incomplete parts

`GET /api/content/seo-audit` and its PHPUnit coverage remained explicitly deferred. Full-page iframe preview, bulk SEO patch, AI-generated metadata, and server-side admin pagination were also outside It.27.

## Verification

The test plan covered mode persistence, featured-image fallback, SEO save/API output, missing-alt badges, SEO-issue filtering, and the media metadata modal. The iteration source names release `2.0.15`, but the supplied changelog contains no standalone entry or stable release anchor for that version.

