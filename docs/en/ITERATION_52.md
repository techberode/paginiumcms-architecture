---
title: Iteration 52 – Dashboard v2, contact, and company data
description: Delivery of dashboard overview, contact configuration, and editable company information.
icon: material/history
---

# Iteration 52 – Dashboard v2, contact, and company data

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete across 52a–52c |
| Release / period | 2.0.35–2.0.36 |
| Record type | historical admin UX delivery record |

## Goal

Extend the admin dashboard with activity, flat-file structure, and KPIs, while adding configurable contact subjects, company information, and a map embed.

## Scope and outcome

It.52a extended `/api/admin/dashboard/overview` with counts and storage data. It.52b added `contact.subjects` and optional custom subjects. It.52c added editable company fields and a Google Maps embed URL to public settings.

The public panel appeared only when enabled and populated; the dashboard reused existing counts and health data rather than creating another source.

## Architecture and security boundaries

External map embeds must be allow-listed and validated; the URL is not trusted HTML. The dashboard flat-file tree is an overview, not a general file browser or a Path ACL bypass.

## Verification and related records

Contact settings shipped in [2.0.35](../../CHANGELOG.md#release-2-0-35), and company data in [2.0.36](../../CHANGELOG.md#release-2-0-36).

## Current interpretation

It.52 is the completed dashboard/contact baseline. Later system overview or host metrics work must not duplicate its counts and storage contracts.
