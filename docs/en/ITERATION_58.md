---
title: Iteration 58 – Page layout builder and color schemes
description: Layout Switch complete: schemes, templates, shortcodes, outline canvas, and compile/cache with It.48.
icon: material/history
---

# Iteration 58 – Page layout builder and color schemes

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ 58b–58f + **58f-h** canvas shipped; **58g** compile/cache with It.48 in `beta.89` |
| Release / period | 58c: 2.1.0-beta.23 |
| Record type | historical product and architecture record |

## Goal

Deliver multiple layout builders selectable in Settings, all writing one canonical layout AST, combined with color schemes, light/dark/system mode, and live preview.

## Scope and outcome

Delivered in 58b: five presets with light/dark tokens, `appearance` settings, swatches and `SchemePreviewFrame`, public application, and visitor toggle. Delivered in 58c: builder switch, template catalog, page template selection, and `LayoutPreviewFrame`; release [v2.1.0-beta.23](../../CHANGELOG.md#release-2-1-0-beta-23).

Delivered in 58d: shortcode expand pipeline (`ShortcodeExpanderService` at render time), bundled catalog seeder, admin `ShortcodesManager` (Monaco JSON + policy preview), page-editor insert panel when builder mode is Shortcodes, and public `PageLayoutShell` wired from `layoutTemplate` front matter.

Delivered in 58e: allow-listed `pg-*` layout utilities in `frontend/src/theme/pgLayout.css` for shortcode expand templates.

Delivered in 58f–58g: outline/DnD canvas, and HTML compile/cache with It.48. The `featureGallery` block reuses the It.65 API without a second store.

## Architecture and security boundaries

All modes must read and write the same AST, and switching must not erase content. Non-core definitions are fail-closed through `ShortcodeDefinitionPolicy` plus `CodePolicyEngine::validateUntrusted`; no `eval`, runtime PHP, or arbitrary Tailwind/classes. Preview uses identical validators.

## Verification and related records

Decisions and the phased plan are in [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md). Security completion for 58d is coupled with planned [It.67](ITERATION_67.md); [It.66](ITERATION_66.md) delivered the write-time baseline.

## Current interpretation (September 2026)

It.58 is closed for product slices. **58b–58e shipped.** Publishing UX **[ITERATION_58f.md](ITERATION_58f.md)** (**58f-a–h shipped**: visual canvas, forms, live preview, DAM hero video, feature-gallery, i18n/help). **58g** compile/cache shipped with [It.48](ITERATION_48.md) in `v2.1.0-beta.89`. Do not invent 58i.

Do not invent It.94 for page blocks.
