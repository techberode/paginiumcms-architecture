---
title: Iteration 46 – Host metrics agent
description: Plan for a lightweight agent collecting CPU, RAM, disk, uptime, and Docker metrics outside PHP requests.
icon: material/history
---

# Iteration 46 – Host metrics agent

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ⏳ Planned |
| Release / period | bez samostatného release |
| Record type | historical monitoring design |

## Goal

Extend application monitoring with host metrics not provided by normal CMS health checks: uptime, load, CPU, RAM, disk, and container state.

## Scope and outcome

The agent would run as a host cron task or sidecar every 1–5 minutes and store a validated snapshot in `data/metrics/host-latest.json`. `MonitoringReportBuilder` and `GET /api/admin/metrics/host` would feed reports and dashboards from the same snapshot.

Proposed settings included `hostMetricsEnabled`, maximum snapshot age, and an ingest token. The design offered Bash and PHP CLI variants without requiring Redis.

## Architecture and security boundaries

Collection must not run inside anonymous HTTP requests. Ingest should be localhost/Docker-network only or protected by token/HMAC and an IP allowlist. Payloads should contain aggregate numbers, not environment dumps, command lines, or secrets.

## Verification and related records

The source defines a design and test fixtures only; it provides no release or deployment evidence. It extends application reports from [It.7](ITERATION_7.md) and the system-overview backlog.

## Current interpretation

It.46 remains a separate planned capability. It must not be confused with external Prometheus/node-exporter monitoring: this design is specifically for a CMS-ingested snapshot displayed by PaginiumCMS.
