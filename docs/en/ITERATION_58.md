---
title: Iteration 58 – Page layout builder and color schemes
description: Partially delivered Layout Switch: schemes and template builder shipped; shortcode, outline, and compile slices remain.
icon: material/history
---

# Iteration 58 – Page layout builder and color schemes

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | 🟡 Partially complete: 58b/58c ✅, 58d–58g ⏳ |
| Release / period | 58c: 2.1.0-beta.23 |
| Record type | historical product and architecture record |

## Goal

Deliver multiple layout builders selectable in Settings, all writing one canonical layout AST, combined with color schemes, light/dark/system mode, and live preview.

## Scope and outcome

Delivered in 58b: five presets with light/dark tokens, `appearance` settings, swatches and `SchemePreviewFrame`, public application, and visitor toggle. Delivered in 58c: builder switch, template catalog, page template selection, and `LayoutPreviewFrame`; release [v2.1.0-beta.23](../../CHANGELOG.md#release-2-1-0-beta-23).

Planned for 58d–58g: shortcode engine plus Monaco definitions, safe `pg-*` utilities, optional outline/DnD, and HTML compile/cache with It.48. The `featureGallery` block must reuse the It.65 API without a second store.

## Architecture and security boundaries

All modes must read and write the same AST, and switching must not erase content. Non-core definitions are fail-closed through `ShortcodeDefinitionPolicy` plus `CodePolicyEngine::validateUntrusted`; no `eval`, runtime PHP, or arbitrary Tailwind/classes. Preview uses identical validators.

## Verification and related records

Decisions and the phased plan are in [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md). Security completion for 58d is coupled with planned [It.67](ITERATION_67.md); [It.66](ITERATION_66.md) delivered the write-time baseline.

## Current interpretation

It.58 is not a closed iteration. Only 58b and 58c are complete; 58d–58g must not be presented as shipped in docs or UI. Compile/cache work must align with It.48/69, and public rendering must not load the admin bundle.
