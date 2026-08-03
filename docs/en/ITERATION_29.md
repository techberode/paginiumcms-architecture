---
title: Iteration 29 – Cron Scheduler and Flat-File Job Queue
description: Historical record of the job registry, scheduler runner, manual queue, and admin Scheduler
icon: material/history
---

# Iteration 29 – Cron Scheduler and Flat-File Job Queue

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented; hardened for production later |
| Release / period | 2.0.18 |
| Record type | historical scheduler/worker iteration |

## Goal

Unify scheduled tasks in a flat-file registry, provide a CLI runner, optional manual queue, run history, and an admin `/scheduler` page.

## Architecture and stores

| File | Purpose |
|---|---|
| `data/jobs/registry.json` | job definitions, CRON, handler, enabled |
| `data/jobs/runs.json` | append-oriented run history |
| `data/jobs/queue.json` | manual/forced run requests |

`ScheduledJobRunner` loaded the registry, evaluated CRON, and delegated through `JobHandlerRegistry`. Seed jobs were backup and monitoring.

## CLI, API and UI

The historical recommended cron executed `scheduler:run` and `worker:process` every minute. The admin API covered list/detail/create/update/delete/run-now/run-due/process-queue. The UI allowed toggling, CRON editing, immediate runs, and forced monitoring reports.

## Later hardening

The current deployment contract requires `flock`, the same PHP/runtime identity as the web process, safe storage permissions, redacted logs, and idempotent jobs. The production `POST .../run` failure is tracked in [ISS-094](ISSUES.md#iss-094); privilege escalation through a system-deploy job was fixed in [ISS-104](ISSUES.md#iss-104).

A worker is not an implicit SUPER_ADMIN. The originating user identity and authorized action scope must be part of the job payload.

## Verification and further scope

Tests covered the CRON evaluator and scheduled runner. A Redis queue was future work and now belongs to the capability-based Hybrid Engine contract, not the mandatory baseline. Release: [2.0.18](../../CHANGELOG.md#release-2-0-18).

