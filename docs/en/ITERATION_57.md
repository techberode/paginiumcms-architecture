---
title: Iteration 57 – Automatic tags and description generator
description: Deterministic tag and SEO-description suggestions without a mandatory external AI provider.
icon: material/history
---

# Iteration 57 – Automatic tags and description generator

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped |
| Release / period | 2.1.0-beta.4 |
| Record type | historical content-assist delivery record |

## Goal

Help editors suggest tags and a short description from title and body while requiring the user to review and explicitly apply the result.

## Scope and outcome

`ContentMetaGenerator` converted Markdown/Tiptap to plain text, used SK/EN stopwords, frequency, and title overlap, and built descriptions at sentence boundaries. `POST /api/admin/content/suggest-meta` accepted type, title, body, and format.

The sidebar exposed Suggest tags and Generate description with a preview diff. Core v1 had no network dependency; an AI hook was only a future plugin extension.

## Architecture and security boundaries

Suggestions must not overwrite front matter automatically. The endpoint needs body-size and rate limits. A later AI layer must preserve the Diff/Apply principle and fresh permission checks.

## Verification and related records

Release: [v2.1.0-beta.4](../../CHANGELOG.md#release-2-1-0-beta-4). The source records network-free unit tests and support for Markdown and Tiptap plain-text extraction.

## Current interpretation

It.57 is the completed deterministic baseline. The It.75 AI agent may extend it, but must not replace it with a mandatory cloud dependency or autonomous Apply/Publish.
