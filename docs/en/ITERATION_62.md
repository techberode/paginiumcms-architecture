---
title: Iteration 62 – Scheduler production hardening and UX
description: Outcome model, Docker storage permissions, CLI smoke commands, and clear completed/skipped/failed states.
icon: material/history
---

# Iteration 62 – Scheduler production hardening and UX

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented |
| Release / period | 2.1.0-beta.9 |
| Record type | historical operations hardening record |

## Goal

Reliably distinguish HTTP success from job outcome and eliminate Docker/host permission failures when persisting scheduler run logs.

## Scope and outcome

`ScheduledJobRunner` introduced `outcome=completed|skipped|failed` with normalized reasons. CLI `jobs:run {id}` enabled isolated smoke tests. When run-log persistence failed, the handler returned `run_log_persisted:false` and diagnostics without turning the business result into a malformed PHP-warning response.

The frontend displayed Completed/Skipped/Failed and separated an amber warning for log persistence. The deployment checklist used setgid directories, a runtime-identity write test, and OPcache restart.

## Architecture and security boundaries

Permissions must be tested as the actual runtime user inside the container; host-user writability is not evidence. Production documentation must not hardcode a maintainer account. A run-log error must not hide a job failure or produce a false green state.

## Verification and related records

Release: [v2.1.0-beta.9](../CHANGELOG.md#release-2-1-0-beta-9). The production 500 failure is recorded in [ISS-094](ISSUES.md#iss-094). The same shared-storage model later appeared in demo operations in [ISS-099](ISSUES.md#iss-099).

## Current interpretation

It.62 is the historical baseline for outcome semantics. New queue, Git, translation, and AI job infrastructure must preserve job identity, idempotency, run correlation, and separation of execution from log persistence.
