---
title: Iteration 54 – Modular Markdown/WYSIWYG editor
description: Editor profiles controlling the toolbar, Tiptap extensions, and backend validation.
icon: material/history
---

# Iteration 54 – Modular Markdown/WYSIWYG editor

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.42 |
| Record type | historical editor delivery record |

## Goal

Deliver a dual Markdown + Tiptap editor stack whose capabilities are controlled by company, blog, minimal, and developer profiles.

## Scope and outcome

`EditorProfileService` and `EditorContentValidator` defined allowed blocks; settings selected default page and article profiles. Front matter stored `editorProfile` and `editorMode`; the frontend rendered toolbar actions and Tiptap extensions dynamically from capabilities.

Disallowed paste/import was blocked in both frontend and server validation. Profile changes did not require a reload.

## Architecture and security boundaries

Hiding a frontend button is not a security control; the backend must validate every save. The developer profile must not automatically permit scripts, iframes, or executable raw content.

## Verification and related records

Release: [2.0.42](../../CHANGELOG.md#release-2-0-42). A later blog-profile failure on fenced code blocks was fixed in [ISS-079](ISSUES.md#iss-079).

## Current interpretation

It.54 is the completed profile baseline for It.55 and It.60. The current extension contract requires the same server-side allow-list validation for custom components.
