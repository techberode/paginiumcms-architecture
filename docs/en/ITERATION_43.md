---
title: Iteration 43 – Advanced Search and Admin Command Palette
description: Historical record of scoped public/admin search, the Ctrl+K palette, and test-artifact cleanup
icon: material/history
---

# Iteration 43 – Advanced Search and Admin Command Palette

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented; the old `Unreleased` label is obsolete |
| Release / period | po 2.0.26, presný prvý tag zdroj neuvádza |
| Record type | historical search and test-infrastructure iteration |

## Goal

Deliver scoped full-text search for the public site and an admin command palette without breaking the legacy flat `/api/search` response used by public clients.

## Backend contract

| Scope | Auth | Types | Shape |
|---|---|---|---|
| `public` | no session | page, article | legacy flat `data: SearchResult[]` |
| `admin` | session | page, article, media, route | `{ query, scope, results, counts }` |

Query parameters were `q` minimum 2, `scope`, comma-separated `types`, and a maximum per-type limit of 20. Admin scope included drafts and a role-filtered route catalog.

## Frontend

`AdminCommandPalette` opened with Ctrl/Cmd+K, `ResponsiveLayout` registered the hotkey, and recent jumps were stored in localStorage. Public `SiteSearchModal` explicitly used `scope=public`.

## Test-infrastructure addition

The same release train introduced `TestStorageCleaner`, the `test-artifacts.php` CLI, isolated `settings.testing.json`, and a cleanup step in the test runner. The current test contract uses per-run isolated storage roots; cleanup based on generic email prefixes is only a transitional safeguard for old shared storage.

## Status and incidents

The source labeled the release track `Unreleased`, but the feature was implemented and later roadmap/feature documentation confirms It.43 completion. The exact first release tag is not supplied and is therefore not invented. Flaky admin draft search was later fixed in [ISS-023](ISSUES.md#iss-023).

## Verification

PHPUnit covered the search controller and cleaner, and Vitest covered the command palette. The current test workflow is in [TESTING.md](developer/TESTING.md).

