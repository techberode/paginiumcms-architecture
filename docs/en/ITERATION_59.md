---
title: Iteration 59 – Scheduled publishing
description: Scheduled publication of pages and articles through the existing scheduler and timezone contract.
icon: material/history
---

# Iteration 59 – Scheduled publishing

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped |
| Release / period | 2.0.53 |
| Record type | historical scheduler and content record |

## Goal

Allow content to be stored as `scheduled` with `scheduledAt` and automatically published at the configured time through the existing job registry.

## Scope and outcome

The editor gained a date-time picker, admin lists gained scheduled filters/columns, and the backend added `content.scheduled_publish`. The job was idempotent, respected OTP publish approval, and used `AppTimezone` including DST.

Scheduled content remained hidden from public API until due; successful execution switched it to published and removed the schedule marker.

## Architecture and security boundaries

Scheduler identity must not bypass content permissions or OTP policy. Time is stored in an unambiguous ISO format and interpreted through application timezone; duplicate execution must be safe.

## Verification and related records

Release: [2.0.53](../../CHANGELOG.md#release-2-0-53). Production outcome and permission hardening for the scheduler was later handled in [It.62](ITERATION_62.md).

## Current interpretation

It.59 is the completed v1 publish scheduler; recurring publishing and scheduled unpublish/archive remain outside the original scope.
