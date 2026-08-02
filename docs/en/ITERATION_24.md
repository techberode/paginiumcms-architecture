---
title: Iteration 24 – Full DAM v1
description: Historical record of the folder-aware Media Library, sidecar metadata, bulk operations, and stock catalog
icon: material/history
---

# Iteration 24 – Full DAM v1

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.12 |
| Record type | historical media/DAM iteration |

## Goal

Extend the original Media Manager into a flat-file Digital Asset Manager with nested folders, sidecar metadata, bulk delete, and configurable MIME/upload-size limits.

## Storage and API

| Element | Implementation |
|---|---|
| Asset registry | `media/registry.json` |
| Folder registry | `media/folders.json` plus `.paginium-folder` marker |
| Sidecar | `{path}.meta.json` with `altText`, `title`, `folder`, and `updatedAt` |
| API | list/folders/create-folder/upload/PATCH metadata/bulk-delete/single delete |
| Settings | `media.allowedMimeTypes`, `media.maxUploadSizeKb` |

## Frontend and stock catalog

`MediaManager` gained breadcrumbs, folder cards, selection/bulk delete, and title/alt editing. The stock library was not a SQL database: it was a flat-file `stock-images.json` catalog with topics and external URLs. The backend imported the binary into Media Library rather than storing only an external link.

## Current security boundaries

The historical record predates later SSRF hardening. Every stock-asset download must now pass outbound URL validation, redirect revalidation, size limits, and MIME/magic-byte checks. Sidecar metadata is authoritative only for asset description; physical binary storage may use local or S3 drivers in a Hybrid Engine profile as defined in [STORAGE.md](architecture/STORAGE.md).

## Deferred and verification

Asset locking, thumbnails, bulk move, and expanded caption/tags UI remained outside v1. Tests covered repository, controller, stock catalog/importer, and frontend folder navigation. Release: [2.0.12](../CHANGELOG.md#release-2-0-12).

