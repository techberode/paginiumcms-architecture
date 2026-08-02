---
title: Iteration 6 – Notifications, Analytics and Authentication UI
description: Historical record of connectors, visit analytics, incident alerts, and complete authentication flows
icon: material/history
---

# Iteration 6 – Notifications, Analytics and Authentication UI

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.1+ |
| Record type | historical feature iteration |

## Goal

Connect notification channels, visit analytics, incident alerts, toast settings, and complete login/recovery flows across backend and SPA.

## Delivered scope

| Area | Main elements |
|---|---|
| Settings | `smtp`, `notifications`, `connectors`, `monitoring` |
| Notifications | `SmtpTransport`, `NotificationFactory`, `IncidentNotifier`, channel adapters |
| Analytics | `Reporter`, `AnalyticsManager`, `AnalyticsMiddleware`, admin API |
| Auth | email password reset, removal of demo token from production responses, failed-login alerts |
| Frontend | `/notifications`, toast behavior from public settings, login/register/forgot/reset routes, change-password modal |

## Configuration and verification

Server setup required SMTP, selected connectors, monitoring alerts, and toast behavior. Verification used the connector test on `/notifications`, the password-reset flow, and controlled toast debugging.

Current secret fields must be encrypted and outbound URLs must pass `OutboundUrlGuard`; the original quick start is not a complete production-hardening guide.

## Tests

The iteration added `NotificationFactoryTest`, `IncidentNotifierTest`, frontend notification-settings tests, and updated authentication tests. Later planned capabilities were split into separate iterations.

