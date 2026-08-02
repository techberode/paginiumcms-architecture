---
title: Iteration 1 – Content Locking System
description: Historical record of content locks, heartbeat handling, and concurrent editing protection
icon: material/history
---

# Iteration 1 – Content Locking System

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.6 – základ jadra |
| Record type | historical foundation iteration |

## Goal

Prevent two users from editing the same document concurrently without a visible conflict. The iteration introduced a flat-file lock registry, a client heartbeat, and server-side TTL expiry.

## Delivered scope

| Layer | Implementation |
|---|---|
| Model | `Core/Locking/Models/ContentLock.php` – owner, token, heartbeat, and expiry |
| Contract | `LockManagerInterface.php` |
| Service | `LockManager.php` over `data/locks.json` with `flock(LOCK_EX)` |
| Conflict | `LockConflictException.php` → HTTP `409` with lock context |
| HTTP | `LockController.php` and auto-discovered routes |
| Frontend | `src/api/locks.ts`, `useContentLock.ts`, `LockIndicator.tsx` |

## Operational parameters

| Parameter | Historical value |
|---|---|
| Client heartbeat | 30 seconds |
| Server TTL | 300 seconds |
| Registry | `backend/storage/app/content/data/locks.json` |

The initial routes were `POST /api/locks/acquire`, `heartbeat`, `release`, and the administrative `GET /api/locks`. Conflicts use the standard `409` envelope documented in [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Verification and continuity

Tests covered the lock manager, HTTP controller, and dashboard panel integration. This iteration provides the pessimistic coordination layer; optimistic revision checks and drafts followed in [Iteration 2](ITERATION_2.md).

