---
title: Iteration 2 – Auto-Save, Versioning and Conflict Detection
description: Historical record of content revisions, drafts, autosave, and optimistic concurrency control
icon: material/history
---

# Iteration 2 – Auto-Save, Versioning and Conflict Detection

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.6 – základ jadra |
| Record type | historical foundation iteration |

## Goal

Extend the locks from It.1 with optimistic concurrency control (OCC), auto-saved drafts, and editor-integrated version history.

## Backend

| Component | Responsibility |
|---|---|
| `ContentRevision.php` | Deterministic fingerprint over body and canonical front matter |
| `ContentConflictException.php` | HTTP `409` with the server version in the `conflict` context |
| `Core/Drafts/` | Drafts under `data/drafts/{type}/{slug}.json` |
| `DraftController.php` | `PUT/GET/DELETE /api/drafts/{type}/{slug}` |
| `ContentController` | `baseRevision` check, commit message, and new revision |
| `VersionManager.php` | Hydration and version history |

## Frontend and contract

`versions.ts`, `drafts.ts`, `useAutoSave.ts`, `DiffViewer.tsx`, and `MarkdownEditor.tsx` formed one editing lifecycle. The historical autosave interval was 60 seconds and later became settings-driven.

The SHA-1 revision fingerprint is a **change identifier for OCC**, not a cryptographic integrity signature. The current contract is documented in [VERSIONING.md](architecture/VERSIONING.md).

## Verification and next step

Tests covered revisions, the draft manager/controller, and `VersionManager`. Automatic and manual conflict merging followed in [Iteration 3](ITERATION_3.md).

