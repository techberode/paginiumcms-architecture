---
title: Iteration 7 – Scheduled Monitoring Reports and Log Incidents
description: Historical record of scheduled reports, log scanning, scheduler orchestration, and notification UI
icon: material/history
---

# Iteration 7 – Scheduled Monitoring Reports and Log Incidents

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.17 |
| Record type | historical operations iteration |

## Goal

Add hourly, daily, and weekly reports, an HTML digest, `ERROR/WARNING` log alerts, and a CLI command suitable for hosting cron.

## Backend

| Component | Responsibility |
|---|---|
| `MonitoringReportBuilder` | Text and HTML report |
| `MonitoringReportScheduler` | Due logic and delivery |
| `LogIncidentScanner` | Scan new application logs |
| `MonitoringScheduler` | Orchestrate report and incident scan |
| `SchedulerStateStore` | `data/scheduler-state.json` |
| `IncidentNotifier::notifyViaConnectorDetailed()` | Specific connector and preflight reasons |

## CLI, API, and UI

The historical CLI command was:

```bash
php backend/bin/console monitoring:run-schedule
```

The admin API exposed overview, schedule, manual report delivery, cron simulation, and connector testing. `/notifications` displays blockers, send-now, and scheduler simulation controls.

## Production cron – current interpretation

The original record recommended chaining backup and monitoring in one minute-level cron line. The current deployment contract prefers a wrapper or container command with `flock`, a defined runtime identity, redacted logging, and preserved exit status. See [CRON.md](deploy/CRON.md).

## Report scope

The digest included traffic, IPs, top pages/articles/referrers, health, and flat-file counts. Host-level CPU, RAM, disk, Docker, SSH, and mail-queue data were outside this iteration.

