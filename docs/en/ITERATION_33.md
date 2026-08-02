---
title: Iteration 33 – Analytics Enrichment
description: Historical record of referrer classification, IP masking, geo visits, and the SPA pageview beacon
icon: material/history
---

# Iteration 33 – Analytics Enrichment

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented |
| Release / period | release neurčený v zdroji |
| Record type | historical analytics iteration |

## Goal

Extend the existing flat-file analytics with readable traffic sources, country/city, masked sample IPs, and recent geo visits without introducing a database.

## Backend and frontend

| Component | Responsibility |
|---|---|
| `RefererAnalyzer` | direct/search/social/referral plus human-readable label |
| `AnalyticsIpMasker` | GDPR-aware IPv4/IPv6 masking for UI |
| Reporter | top referrers, geo statistics, and recent geo visits |
| Frontend | country-flag utility, Sources/Geo tabs, and typed API payloads |

## SPA pageview fix

A static nginx frontend did not pass through PHP `AnalyticsMiddleware`. The solution was a public `POST /api/analytics/pageview`, path validation, three-second cache deduplication, and `useAnalyticsPageview()` on React Router navigation. The beacon respects analytics consent when the cookie banner is enabled.

## Security and privacy boundaries

The endpoint is CSRF-exempt because it is a public beacon, but it must remain rate-limited and path-validated and must reject `/api/*` and traversal. Masked IPs in the UI do not remove retention and protection requirements for raw analytics data.

## Verification

Acceptance covered source labels, flags/city/masked IPs, unit tests for analyzer/masker/reporter, and the country-flag Vitest. The source also lists related CI hotfixes that are not part of the analytics feature itself.

