---
title: Iteration 22 – Operations Completion and Public Discoverability
description: Historical record of trash UI, brute-force lockout, RSS, sitemap, and the same-origin deployment guard
icon: material/history
---

# Iteration 22 – Operations Completion and Public Discoverability

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; one smoke item remained unconfirmed in the source |
| Release / period | 2.0.10 |
| Record type | historical operations iteration |

## Goal

Close the remaining production gaps after API stabilization: user-facing trash restore, brute-force login protection, and public RSS/sitemap endpoints.

## Delivered scope

| Feature | Contract |
|---|---|
| Trash | typed `trashApi`, `/trash`, restore action, and admin sidebar link |
| Lockout | counters by IP and email in a flat-file store, configurable limits, and HTTP `429` |
| RSS | `GET /feed.xml`, RSS 2.0 from published articles |
| Sitemap | `GET /sitemap.xml`, published pages and articles |
| Settings | `feeds` and `security` groups |
| Deployment | same-origin production build/proxy guard |

## Editorial correction

Some backend blocks in the source are labeled “planned” even though the header, scope, and release status mark them complete. The canonical historical record follows the **Complete** release status, but does not present the unmarked Newman smoke item as verified: `GET /feed.xml` and `GET /sitemap.xml` in the Newman collection remained `⏳`.

## Security boundaries

Login lockout requires safe file locking and must not leak state across isolated tests. Feed and sitemap are public but read published content only. Same-origin deployment remains preferred so session, CSRF, and media URLs do not use inconsistent origins.

## Verification and continuity

The release is recorded under [CHANGELOG 2.0.10](../../CHANGELOG.md#release-2-0-10). It follows [It.21](ITERATION_21.md) and prepares the SEO meta engine in [It.23](ITERATION_23.md).

