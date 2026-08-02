---
title: Iteration 50 – In-app micro firewall
description: Delivery of a lightweight PHP WAF with incidents, jails, bans, and admin UI.
icon: material/history
---

# Iteration 50 – In-app micro firewall

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.26 |
| Record type | historical security delivery record |

## Goal

Add an early defensive layer before Slim routing that detects common probes, traversal, and suspicious URI/query/UA patterns, records incidents, and applies temporary or permanent bans.

## Scope and outcome

Delivery included a scenario registry, scanner, flat-file ban store, incident ring buffer, middleware, admin API, and React `FirewallManager`. Defense was split into detection, jail, and permanent lock; whitelisting and manual unban took precedence.

Settings controlled the master toggle, jail duration, retry threshold, permanent threshold, response mode, and retention. Tests covered false positives, expiry, escalation, middleware, and admin-only operations.

## Architecture and security boundaries

Editor bodies must not be scanned by context-free SQL/XSS regexes. Tarpit is disabled by default, proxy IP handling uses trusted proxies, and public settings must not expose ban registries. The historical proposal mentioned optional SQLite storage; that conflicts with the current No-SQL mandate and is not the current path.

## Verification and related records

The release is [2.0.26](../CHANGELOG.md#release-2-0-26). A later audit found a POST/JSON body-scan gap and closed it in [ISS-056](ISSUES.md#iss-056). The user-facing contract is in [user/FIREWALL.md](user/FIREWALL.md).

## Current interpretation

It.50 is the implemented baseline, not a replacement for nginx/host firewalling, rate limiting, or secure input validation. The current contract remains flat-file first and fail-safe.
