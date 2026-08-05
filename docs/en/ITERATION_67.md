---
title: Iteration 67 – Untrusted surfaces and defense in depth
description: Plan to complete shortcode/theme/module authoring security, CSP hygiene, and hostile fixtures.
icon: material/history
---

# Iteration 67 – Untrusted surfaces and defense in depth

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped (foundation) |
| Release / period | `v2.1.0-beta.27` |
| Record type | historical security backlog record |

## Goal

Complete defense in depth for untrusted authoring surfaces without increasing anonymous-render cost.

## Scope and outcome

67a would wire `ShortcodeDefinitionPolicy → validateUntrusted → write → registry update` into every Monaco/API save and use identical validators in preview. 67b extends plugin-import parity to theme/module ZIPs. 67c addresses CSP inventory and dependency disposition, while 67d adds a hostile-fixture corpus and one security-regression command.

Explicit non-goals include pixel-builder iframes, runtime user PHP, and ML WAF on public traffic.

## Architecture and security boundaries

Hostile shortcode/plugin fixtures must never be written or activated. Plugin-registered shortcodes get no exception, preview has no bypass, and public rendering uses AST/sanitized output only. CSP changes must be careful, and residual `style-src 'unsafe-inline'` may be a documented risk rather than silently ignored.

## Verification and related records

The source is an open plan with no release. It depends on [It.66](ITERATION_66.md), the open 58d slice in [It.58](ITERATION_58.md), and the It.15 plugin baseline. Dependency review links [ISS-089](ISSUES.md#iss-089).

## Current interpretation

It.67 foundation is shipped: shortcode save/preview/registry, theme ZIP import with policy parity, CSP hardening, and hostile fixture regression pack. Full 58d Monaco UI and public shortcode render remain follow-ups.
