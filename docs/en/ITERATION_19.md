---
title: Iteration 19 – Admin UX, Security Runtime and Authentication
description: Historical record of the admin shell, translation policy, runtime security, and authentication UX
icon: material/history
---

# Iteration 19 – Admin UX, Security Runtime and Authentication

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Delivered in waves 19a–19d and extended in 2.0.46 |
| Release / period | 2.0.44–2.0.46 |
| Record type | historical admin consolidation iteration |

## Goal

Unify admin navigation, settings, the translation editor, runtime security validators, and authentication screens into one coherent administration environment.

## Delivered scope

| Wave | Delivery |
|---|---|
| 19a | Sidebar groups, navigation collapse, settings categories, URL deep links, and staged translation validation |
| 19b | `UploadSecurityValidator` in MediaRepository, `ContentSecuritySanitizer` in rendering, Monaco markers, and `AdminHintCard` |
| 19c | dynamic locale registry, locale scaffolding, user avatars, and SuperAdmin guards |
| 19d | `AuthShell`, TOTP input, configurable login content, and settings-backed password policy |
| 2.0.46 additions | audit activity, application-log formatting, login background picker, and further i18n migrations |

## Incidents and hotfixes

This release train included [ISS-044](ISSUES.md#iss-044) through [ISS-050](ISSUES.md#iss-050): a DI configuration parse error, missing locale-scaffold property, incorrect audit-log classification, an empty activity panel, unreadable audit messages, an empty daily log loop, and mismatched reader/writer paths.

The original record used generated heading anchors. Canonical links now target the stable `#iss-xxx` identifiers introduced in Iteration 13.

## Current interpretation

The delivered admin UX remains foundational, but the current security contract is stricter: secrets must not be exposed or logged, extension code passes write-time policy checks, and frontend locale loading must not be described as runtime-dynamic while it still depends on a Vite build.

The source left further i18n migration, optional dynamic FE locale loading, and registry-backed `general.language` as remaining work. The later It.73/76/77 content-localization branch is a separate Hybrid Engine capability.

## Verification and continuity

Release records: [2.0.44](../CHANGELOG.md#release-2-0-44), [2.0.45](../CHANGELOG.md#release-2-0-45), and [2.0.46](../CHANGELOG.md#release-2-0-46). Related historical documents: [Iteration 18](ITERATION_18.md) and [Iteration 20](ITERATION_20.md).

