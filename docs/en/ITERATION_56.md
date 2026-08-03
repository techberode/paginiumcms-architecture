---
title: Iteration 56 – Rich navigation items
description: Descriptions, Lucide/media icons, thumbnails, and hover preview in flat-file navigation.
icon: material/history
---

# Iteration 56 – Rich navigation items

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.1.0-beta.5 |
| Record type | historical navigation UX record |

## Goal

Extend `navigation.json` with descriptions, icons or thumbnails, and desktop hover previews while preserving mobile and reduced-motion behavior.

## Scope and outcome

The model added `description`, `iconType`, `iconValue`, `previewOnHover`, `previewScale`, and `thumbnailSize`; global defaults lived under `navigationUi`. Admin UI gained a media picker and live row preview, while the public Navbar rendered secondary text and an optional tooltip.

Legacy `icon` values were migrated on read, and validation covered description length, icon type, and media paths.

## Architecture and security boundaries

Media paths must pass the existing allow-list/ACL layer. Hover cannot be the only way to access information; mobile and keyboard UI need equivalents. `prefers-reduced-motion` is respected.

## Verification and related records

Release: [v2.1.0-beta.5](../../CHANGELOG.md#release-2-1-0-beta-5). Dynamic Lucide lookup and desktop descriptions were finalized in [ISS-085](ISSUES.md#iss-085).

## Current interpretation

It.56 is the completed navigation contract. Future plugin icon packs must extend registries safely and must not inject unverified React components from runtime ZIPs.
