---
title: Iteration 63 – Admin system update
description: SUPER_ADMIN-only production code deployment from GitHub tags with privileged job policy and webhook.
icon: material/history
---

# Iteration 63 – Admin system update

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ MVP + v2/v3 shipped; v4 UX deferred |
| Release / period | 2.1.0-beta.18 |
| Record type | historical privileged-deploy record |

## Goal

Allow SUPER_ADMIN to trigger production code deployment from admin UI without routine SSH, while the source explicitly excluded demo and customer bundles.

## Scope and outcome

Delivery included status/check/run APIs, `system:deploy --ref`, an allow-listed deployment script, encrypted GitHub settings, compare/release-notes UI, and a privileged `system-deploy` job. v2 added one-click latest-tag deployment and commit comparison; v3 added a release-published webhook with HMAC, idempotent enqueue, and maintenance/WAF exemptions.

The v4 Grav-like onboarding, pre-update backup prompt, and human-readable progress were deferred to It.25 before Final 1.0.

## Architecture and security boundaries

Code deployment is separate from content Git synchronization. Refs must be allow-listed, the job must not be triggerable through a generic ADMIN jobs endpoint, and webhooks need HMAC, replay/idempotency handling, and outbound protection. The deployment script is the only privileged execution bridge; arbitrary shell arguments are forbidden.

## Verification and related records

Release: [v2.1.0-beta.18](../../CHANGELOG.md#release-2-1-0-beta-18). The privilege bypass was closed in [ISS-104](ISSUES.md#iss-104). The source lists ISS-105, but the current register assigns ISS-105 to GeoIP; the relevant GitHub outbound finding is [ISS-108](ISSUES.md#iss-108).

## Current interpretation

It.63 is the technical deployment engine, not a complete end-user updater. Current release engineering still requires backup, clean evidence, health/smoke checks, and rollback; an admin click must not bypass those gates.
