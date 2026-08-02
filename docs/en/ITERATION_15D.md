---
title: Iteration 15d – Hook Emitters and Extension Policy
description: Historical record of the typed hook catalog, manifest validation, and reference plugin
icon: material/history
---

# Iteration 15d – Hook Emitters and Extension Policy

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped |
| Release / period | 2.0.54 |
| Record type | historical Wave 5d increment |

## Goal

Complete the plugin runtime so Core explicitly emits canonical hooks and extensions subscribe declaratively through a validated manifest.

## Delivered scope

| Class / element | Responsibility |
|---|---|
| `HookCatalog` | canonical hook names and metadata |
| `HookEmitter` | typed wrapper over `HookManager` |
| `ExtensionManifestValidator` | `id`, hooks, `minCmsVersion` |
| `AppVersion` | CMS semver |
| Core emitters | save/delete/status/scheduled publish plus extension lifecycle |
| `hello-widget` | reference PHP route and frontend extension |

## Hooks

The initial set included `content.before_save`, `content.after_save`, `content.after_delete`, `content.after_status_change`, `content.after_scheduled_publish`, `extension.boot`, `extension.enabled`, and `extension.disabled`. A hook callback must not bypass authorization, the SSOT transaction model, or audit logging.

## Tests and incident

The iteration added emitter, manifest, and reference-plugin tests. [ISS-075](ISSUES.md#iss-075) removed a duplicate-class fatal by ensuring the test fixture no longer collided with the reference `hello-widget`.

## Smoke

Enable `hello-widget`, call `/api/extensions/hello-widget/ping`, and save content while verifying the `content.after_save` context.

