---
title: Iteration 55 – Tiptap JSON storage and media upload
description: Structured Tiptap JSON in flat-file storage, server rendering, and editor media upload.
icon: material/history
---

# Iteration 55 – Tiptap JSON storage and media upload

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.43 |
| Record type | historical content-format delivery record |

## Goal

Store WYSIWYG documents as structured `tiptap_json`, validate them against profiles, and render sanitized public HTML without regressing Markdown.

## Scope and outcome

`TiptapHtmlRenderer`, `ContentBodyRenderer`, and `JsonContentStorage` provided JSON → disk → HTML-cache round trips. API used `contentFormat`, the frontend persisted `getJSON()`, and paste/drop/file-picker uploads reused the existing DAM endpoint.

Public reads returned cached HTML for Tiptap records while preserving legacy Markdown and raw-HTML compatibility paths.

## Architecture and security boundaries

The node whitelist must mirror profile capabilities, unsafe URLs are rejected, and script/iframe nodes are not allowed. Cached HTML is derived data and must be regenerated from the SSOT after corruption or renderer-version changes.

## Verification and related records

Release: [2.0.43](../CHANGELOG.md#release-2-0-43). Delivery also included the authentication retry fix [ISS-042](ISSUES.md#iss-042).

## Current interpretation

It.55 is the authoritative structured-editor storage baseline. Layout AST, shortcodes, and AI must not introduce a second inconsistent content-body model.
