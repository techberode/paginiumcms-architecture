---
title: Iteration 61 – Newsletter, subscribers, and public consent UX
description: Multi-phase newsletter delivery from collection and admin listing to sending, double opt-in, preferences, and footer UX.
icon: material/history
---

# Iteration 61 – Newsletter, subscribers, and public consent UX

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete across phases 1–5 |
| Release / period | 2.1.0-beta.16–18 (neskoršie fázy) |
| Record type | historical multi-phase module record |

## Goal

Unify footer and maintenance subscriptions in one flat-file registry, add admin listing/export, then deliver safe sending, double opt-in, unsubscribe, preference management, and polished footer UX.

## Scope and outcome

Baseline delivery included `POST /api/newsletter/subscribe`, admin `/newsletter`, deduplication, and a source column. Phase 1 added preferences, consent, and dedicated rate limiting; Phase 2 added weekly digest/new-article mail and scheduler state; Phase 3 added double opt-in and HMAC unsubscribe; Phase 4 added preference management and CMS release campaigns; Phase 5 simplified the footer to inline email plus modal.

Cookie consent introduced `privacy` settings and gated functional storage, including the public theme preference. A wiring audit confirmed eight endpoints, maintenance bypass for confirm/manage/unsubscribe, and sidebar counts.

## Architecture and security boundaries

Subscriber data is personal data: admin listing is ADMIN+, CSV export must be injection-safe, and logs must not expose tokens. Confirmation tokens are stored as hashes with expiry; outbound mail is gated by a master switch, batch limits, and preference/status filters. Public subscribe needs a honeypot, generic responses, and rate limiting.

## Verification and related records

The original admin-list gap is [ISS-097](ISSUES.md#iss-097). Dedicated newsletter abuse hardening is [ISS-107](ISSUES.md#iss-107). Footer variant B resolved [ISS-109](ISSUES.md#iss-109) and shipped in [v2.1.0-beta.18](../../CHANGELOG.md#release-2-1-0-beta-18); the wiring audit is marked `v2.1.0-beta.16`.

## Current interpretation

It.61 is a completed multi-phase module, not merely a footer form. Future campaigns must reuse the existing subscriber status/preferences SSOT and scheduler identity rather than creating a second mailing list.
