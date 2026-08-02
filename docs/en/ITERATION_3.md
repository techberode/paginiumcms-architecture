---
title: Iteration 3 – Conflict Resolution
description: Historical record of three-way merging, manual resolution UI, and conflict logging
icon: material/history
---

# Iteration 3 – Conflict Resolution

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.6 – základ jadra |
| Record type | historical foundation iteration |

## Goal

Turn a `409` response into a usable workflow: automatic line-based diff3 merge, per-hunk manual decisions, and an administrative conflict registry.

## Implementation

| Layer | Component |
|---|---|
| Backend model | `ConflictRecord.php` |
| Backend store | `ConflictLogger.php` → `data/conflicts.json`, capped at 200, `flock` protected |
| HTTP | `GET/DELETE /api/admin/conflicts` |
| Merge | `src/utils/merge3.ts` – line-based diff3 with LCS anchors |
| UI | `ConflictResolver.tsx` – Mine / Server / Both / Manual |
| Integration | `MarkdownEditor.tsx` – auto-merge after `409`, resolver for dirty results |

## UX and audit

Users received distinct toast states for an automatic merge, an unresolved conflict, and cancellation. The dashboard `ConflictsPanel` read the same flat-file registry. The registry is a diagnostic/audit aid, not a replacement for content versions.

## Tests

`merge3.test.ts` covered 13 scenarios, complemented by `ConflictResolver` and controller tests. The `409 conflict` envelope is documented in [API_CONTRACT.md](architecture/API_CONTRACT.md).

