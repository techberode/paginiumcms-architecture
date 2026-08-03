---
title: It.58 – Layout builder alternatives decision
description: Decision record for Paginium Layout Switch, shared AST, Monaco, and fail-closed protection.
icon: material/history
---

# It.58 – Layout builder alternatives decision

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | 📐 Decision record; 58c shipped, later phases open |
| Release / period | snapshot 2.1.0-beta.21; 58c neskôr beta.23 |
| Record type | historical architecture decision record |

## Goal

Choose a Paginium-native page-building model without a heavy pixel canvas: templates, shortcodes, optional outline, and developer Monaco as switchable views over one layout AST.

## Scope and outcome

The decision rejected react-grid-layout, GrapesJS/Elementor models, arbitrary Tailwind/inline styles, and role-exclusive builders. A Settings switch selects the working mode; roles only gate the more sensitive Developer mode.

Phases: 58c templates plus preview; 58d shortcode registry/parser/Monaco; 58e `pg-*`; 58f outline; 58g compile/cache. Templates, shortcodes, and outline must interoperate through the shared AST.

## Architecture and security boundaries

Maximum protection has six layers: syntax/JSON parsing, security scan, code policy, artifact schema, expansion allow-list, and runtime AST rendering without user PHP. Untrusted paths are validated even when the core editor policy toggle is off. Broken or hostile artifacts return 422 before write and registry update.

## Verification and related records

The source was updated at `v2.1.0-beta.21` with 58b shipped. The later main record confirms 58c in [v2.1.0-beta.23](../../CHANGELOG.md#release-2-1-0-beta-23). Open decisions remain around SSOT synchronization during mode switching and Monaco placement.

## Current interpretation

This document is the governing architecture decision record for open 58d–58g work. Future plugin/theme studios may reuse the pattern but are outside It.58.
