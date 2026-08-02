---
title: Iteration 60 – Custom editor components
description: Plugin-registered Markdown/WYSIWYG blocks with profile allow-list validation.
icon: material/history
---

# Iteration 60 – Custom editor components

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented; exact first release not confirmed |
| Release / period | bez jednoznačného tagu v zdroji |
| Record type | historical extension and editor record |

## Goal

Allow ADMIN/EDITOR roles to extend Markdown and Tiptap editors with custom components from plugin manifests and control them per editor profile.

## Scope and outcome

`EditorComponentRegistry` combined `plugin.json` declarations with profiles; Markdown used `:::component-id`, WYSIWYG used dynamic Tiptap node extensions, and Settings exposed a profile-by-component matrix. The backend rejected unknown or disallowed blocks.

The reference `hello-widget` demonstrated both Markdown and WYSIWYG variants, with toolbar actions shown only when enabled.

## Architecture and security boundaries

Neither plugin manifests nor frontend UI can be the only gate. The save path requires server-side registry/profile validation and extension import policy. A new React component from a ZIP may not be runtime-loadable without a frontend build/redeploy.

## Verification and related records

The source marks the iteration implemented but does not identify a release. Integration of the reference plugin later caused the duplicate-class fatal [ISS-075](ISSUES.md#iss-075), fixed in 2.0.54.

## Current interpretation

It.60 is the implemented registry and validation baseline. The current extension code policy and Vite build-time boundary from documentation It.06 take precedence over any simplified claim that a plugin ZIP adds React UI at runtime.
