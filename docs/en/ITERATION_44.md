---
title: Iteration 44 – Filters, Sorting and Public Blog Pagination
description: Historical record of URL-synchronized admin-list filters and the server-backed public blog API
icon: material/history
---

# Iteration 44 – Filters, Sorting and Public Blog Pagination

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete across waves 44a–44d |
| Release / period | 2.0.37 |
| Record type | historical content-list/API iteration |

## Goal

Standardize filters and sorting across the public blog and admin lists, synchronize state into URLs, and move the public article list to server-side pagination with tag metadata.

## Delivery waves

| Wave | Scope |
|---|---|
| 44a | `blogItemsPerPage`, URL `page/tag/sort`, tag facets, sort dropdown, and article previous/next |
| 44b | shared admin filter bar, Pages/Articles query parameters, and `ui.openLinksInNewTab` |
| 44c | URL filters for Media, Comments, and Trash |
| 44d | backend tag/author/date filters, paginated public articles, `tags[]`, and `total_published` metadata |

## API and settings contract

The public endpoint used `GET /api/articles?page=&per_page=&tag=&sort=`. `content.blogItemsPerPage` was separate from admin `content.itemsPerPage`; `showReadingTime` controlled UI and `ui.openLinksInNewTab` controlled preview/external-link behavior.

The URL is shareable filter state, not an authorization source. The backend must validate filter/sort parameters again, and the public API returns published content only.

## Incident and verification

Backend It.44d tests for tag/date filters were later fixed in [ISS-038](ISSUES.md#iss-038). The test gate covered repository/controller tests and the `blogArticles` utility. Release: [2.0.37](../CHANGELOG.md#release-2-0-37).

## Current interpretation

The source marks the iteration complete with no remaining scope. Later cache/index capabilities must preserve the same filtering semantics and metadata payload; a derived index must never replace the flat-file SSOT.

