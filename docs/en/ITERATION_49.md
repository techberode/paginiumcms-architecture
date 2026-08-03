---
title: Iteration 49 – Unified cache layer
description: Product-level file/Redis cache with auto-detection and safe fallback, absorbed into It.69.
icon: material/history
---

# Iteration 49 – Unified cache layer

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ⏳ Planned; absorbed into It.69 |
| Release / period | bez samostatného release |
| Record type | historical cache design |

## Goal

Unify request memory, file cache, and optional Redis behind one hosting-aware contract, exposing driver choice, diagnostics, and purge controls in admin.

## Scope and outcome

Proposed modes were `auto`, `file`, `redis`, and `memory`. `CacheCapabilityProbe` would verify the extension/client, ping, timeout, and hosting profile; `CacheDriverFactory` would build the chain and `CacheChecker` would report driver, hit rate, and latency.

Beyond content cache, the design covered queues, rate limits, sessions, and edit locks. Flat-file content remained the SSOT and Redis always had a file fallback.

## Architecture and security boundaries

Auto mode must not decide based only on a reachable Redis port. It needs a successful capability test, valid configuration, a namespace/prefix, and a tested degradation path. Cached data must not be authoritative or contain secrets.

## Verification and related records

The source explicitly says implementation is tracked in [It.69](ITERATION_69.md) and that It.49 includes the full It.45 scope. There is therefore no separate release.

## Current interpretation

> **Implementation status:** ✅ shipped in It.69 (Classic scope). Full Redis driver remains a follow-up.
