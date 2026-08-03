---
title: Iteration 20 – Analytics, Dashboard Storage and Indexing Control
description: Historical record of the analytics dashboard, storage statistics, and robots/noindex control
icon: material/history
---

# Iteration 20 – Analytics, Dashboard Storage and Indexing Control

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.46 · 2026-07-21 |
| Record type | historical analytics iteration |

## Goal

Deliver a dedicated Analytics admin page, extend the dashboard with quick links and flat-file content size, and provide a global switch for public-site indexing.

## Backend and frontend

| Layer | Main elements |
|---|---|
| Analytics backend | visit enrichment with device, browser, country, and city; 7/14/30-day periods; `geo`, `browsers`, `top_articles` |
| Dashboard backend | `ContentStorageStatsService` and `storage.content` block |
| SEO | `seo.allowSearchIndexing`, `RobotsTxtGenerator`, and global `noindex` in `SeoMetaBuilder` |
| Frontend | `/analytics`, KPI cards, five tabs, dashboard quick links, and disk panel |

## Security and data boundaries

Analytics remains flat-file and does not introduce a database subsystem. The indexing switch is a global fallback; per-page robots rules remain a separate layer. Geolocation and IP handling were later extended with masking and a consent-aware SPA beacon in [Iteration 33](ITERATION_33.md).

## Verification

The historical checklist required working KPI/tabs on `/analytics`, four quick links and a disk panel on `/dashboard`, `Disallow: /` when indexing is disabled, and a public `noindex` meta tag. The release is recorded under [CHANGELOG 2.0.46](../../CHANGELOG.md#release-2-0-46).

## Related records

The iteration follows [Iteration 19](ITERATION_19.md). [ISS-046](ISSUES.md#iss-046) through [ISS-050](ISSUES.md#iss-050) belong to the same release train but concern audit/logging rather than the analytics calculations themselves.

