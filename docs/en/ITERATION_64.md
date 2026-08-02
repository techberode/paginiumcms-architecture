---
title: Iteration 64 – Footer social links
description: Editable social/project links with a platform allow-list and public settings slice.
icon: material/history
---

# Iteration 64 – Footer social links

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped |
| Release / period | 2.1.0-beta.19 |
| Record type | historical marketing UX record |

## Goal

Let admins manage footer social/project links without hardcoded theme URLs and render them with platform icons.

## Scope and outcome

The `marketing` settings group stored a master toggle and normalized JSON list. `SocialLinksNormalizer` validated the platform allow-list, URLs, and a maximum of 12 entries; public settings exposed only `social.enabled` and `social.links[]`.

The admin panel supported add/edit/remove/reorder, and the footer mapped platforms to Lucide icons.

## Architecture and security boundaries

URL validation must block `javascript:` and unsafe schemes; external links must use safe `rel` attributes. Email/RSS use explicitly permitted schemes, not a general bypass.

## Verification and related records

Release: [v2.1.0-beta.19](../CHANGELOG.md#release-2-1-0-beta-19). The source records PHPUnit/Vitest coverage and a public-settings smoke check.

## Current interpretation

It.64 is a completed small marketing module. Future icon packs or custom networks must extend an allow-list/registry rather than accepting arbitrary component names or raw SVG.
