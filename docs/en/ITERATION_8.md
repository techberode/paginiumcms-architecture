---
title: Iteration 8 – Media Manager Frontend
description: Historical record of the admin media library, editor integration, media picker, and same-origin API fix
icon: material/history
---

# Iteration 8 – Media Manager Frontend

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; extended later |
| Release / period | 2.0.4 + post-2.0.20 editor doplnenia |
| Record type | historical frontend iteration |

## Goal

Connect the existing `/api/media` endpoints to a real admin UI with drag-and-drop upload, alt-text editing, an editor/SEO media picker, and a same-origin API URL fix for LAN deployments.

## Frontend

| File | Responsibility |
|---|---|
| `src/api/media.ts` | List, upload, patch, delete, and URL helpers |
| `MediaManager.tsx` | Grid/list UI, upload, metadata, delete |
| `MediaPickerModal.tsx` | Image selection for editor and OG/thumbnail |
| `SeoMetadataPanel.tsx` | SEO media integration |
| `MarkdownEditor.tsx` | Markdown/WYSIWYG toggle and media insertion |
| `apiBaseUrl.ts` | Same-origin fallback without `VITE_API_URL` |

## API and deployment

All media routes require session authentication. Nginx must proxy `/api` before the SPA fallback; otherwise the client receives HTML instead of JSON. Current rules are in [NGINX_API.md](deploy/NGINX_API.md).

## Later extensions

Full DAM capabilities (folders and bulk operations) arrived later. Monaco and Developer unlock were added after 2.0.20 and are described in more detail in [Iteration 16](ITERATION_16.md).

## Tests

PHPUnit covered the repository/controller; Vitest covered media API helpers, manager UI, and the same-origin resolver.

