---
title: Iteration 42 – Admin Item Counts and List Controls
description: Historical record of role-aware counts, list toolbar, pagination, and trash bulk operations
icon: material/history
---

# Iteration 42 – Admin Item Counts and List Controls

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | release neurčený v zdroji |
| Record type | historical admin-list UX iteration |

## Goal

Replace client-side sidebar-count estimates with a role-aware backend endpoint and standardize search/filter/sort/page-size/pagination controls across admin lists.

## Backend

`AdminCountsService` aggregated counts from flat-file repositories. `GET /api/admin/counts` returned content/media/backups to EDITOR and additionally comments/messages/trash/users to ADMIN. `ui.showListCounts` controlled badge visibility and `ui.adminListPageSize` the default.

Trash API gained bulk purge, backup, empty, and backup-download endpoints.

## Frontend

`getAdminCounts()` plus `useAdminCounts()` powered the sidebar. `AdminListToolbar`, `AdminListPagination`, `useAdminListPageSize()`, and `applyClientListView()` formed shared list UX for Media, Pages/Articles, Comments, and Trash.

## Security boundaries

A role-aware payload is not merely visual filtering; the server must not return admin-only counts to an unauthorized user. Trash purge/empty/download are mutating or sensitive operations and remain behind authorization, CSRF, and policy-driven 2FA.

## Verification

Tests covered editor/admin field visibility, trash bulk purge and empty, and the client-side filter/sort/paginate helper. URL-synchronized filters followed in [It.44](ITERATION_44.md).

