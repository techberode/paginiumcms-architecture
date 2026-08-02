---
title: Iteration 45 – Redis as an optional infrastructure layer
description: Historical Redis driver, shared cache, queue, and lock design; scope absorbed by It.69.
icon: material/history
---

# Iteration 45 – Redis as an optional infrastructure layer

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ⏳ Planned; absorbed into It.69 |
| Release / period | bez samostatného release |
| Record type | historical infrastructure design |

## Goal

Introduce Redis only as an optional shared layer for multiple PHP workers or replicas. Flat-file content, settings, and media were to remain authoritative.

## Scope and outcome

The source proposed a `MemoryDriver → RedisDriver → FileDriver` chain, optional `RedisJobQueueStore`, TTL locks, shared rate limiting, and only later a session handler. A `scheduler.queueDriver` feature flag would select `flatfile|redis`; admin UI would expose a connection test and active queue driver.

Redis was not a blocker for the single-node profile. It was justified only for 2+ PHP processes/replicas, queue or lock contention, or measurable disk-cache latency.

## Architecture and security boundaries

Redis must not become the primary content store or a boot requirement. An outage must produce a tested file-driver fallback, not a CMS outage. Password/TLS configuration belongs in encrypted settings or environment variables, and keys must not contain secrets.

## Verification and related records

The source does not identify an implementation release. Its driver-level design was later merged with It.49 and canonically moved to [It.69](ITERATION_69.md). The original scheduler is recorded in [It.29](ITERATION_29.md).

## Current interpretation

It.45 is now a reference design rather than a separate active backlog item. Implementation belongs to It.69 and must use a capability probe; Redis must not be enabled “magically” merely because a socket is reachable.
