---
title: Iteration 28 – Platform Bulk Actions
description: Historical record of shared bulk-selection UI and per-item batch API results
icon: material/history
---

# Iteration 28 – Platform Bulk Actions

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.16 |
| Record type | historical platform iteration |

## Goal

Extract bulk selection from Media Library into shared hooks/UI and introduce a consistent backend batch result with partial successes and failures.

## Shared contract

`useBulkSelection`, `BulkActionBar`, and the FE `BulkBatchResult` type complemented backend `Http/Support/BulkBatchResult.php`. A batch response reports `processed`, `succeeded`, `failed`, and per-item `results[]`; the operation is therefore not necessarily all-or-nothing.

The frontend must surface partial failure instead of showing only a generic success toast.

## Covered modules

| Module | Bulk actions |
|---|---|
| Media | delete selected |
| Pages/Articles | publish, draft, archive, delete |
| Trash | restore selected |
| Comments | approve, reject, delete |
| Users | delete with self/last-SUPER_ADMIN guards |
| Backups | restore/delete/import ZIP/download/verify SHA-256 |

## Security boundaries

Bulk mutations must re-check permissions for each operation and respect 2FA/CSRF. Backup ZIP import is later covered by the Zip-Slip hardening in [ISS-088](ISSUES.md#iss-088). Per-item results must not bypass the audit trail.

## Deferred and verification

Bulk SEO patch, message mark-read, and a generic registry remained outside 2.0.16. Tests covered the aggregator, content/trash controllers, and selection hook. Release: [2.0.16](../../CHANGELOG.md#release-2-0-16).

