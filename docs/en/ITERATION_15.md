---
title: Iteration 15 – External Plugins and Runtime
description: Historical record of ZIP import, plugin registry, hook runtime, extension routes, and admin UI
icon: material/history
---

# Iteration 15 – External Plugins and Runtime

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.38 |
| Record type | historical extension iteration |

## Architectural law

Plugin code must not become part of `Core/`:

```text
backend/app/Http/Extensions/{id}/      PHP extension code
backend/app/Http/Routes/extensions/    routes for enabled extensions
frontend/src/extensions/{id}/          frontend source included in the build
```

This separation prevents external code from owning platform primitives.

## Delivered scope

| Area | Component |
|---|---|
| Registry | `data/plugins.json`, `PluginRegistry`, `flock` |
| Import | `PluginImporter`, policy scanner, `CodePolicyEngine` |
| Lifecycle | list/import/enable/disable/uninstall |
| Runtime | `PluginManager`, `HookManager`, enabled extension routes |
| API/UI | `/api/admin/extensions*`, `ExtensionsManager`, sidebar |
| Frontend discovery | `frontend/src/extensions/loader.ts` through `import.meta.glob` |

## Important frontend-runtime boundary

`import.meta.glob` is **build-time discovery**. PHP plugin import can be a runtime operation, but a new React/TypeScript bundle does not appear without a build/redeploy step. The current contract is in [PLUGINS.md](architecture/PLUGINS.md).

## Import and security

ZIP import requires a manifest, path canonicalization, Zip-Slip/symlink protection, limits, and staged scanning. A plugin remains disabled after import; activation is a separate authorized operation.

## Tests

The registry, manager, importer, controller, and frontend loader had dedicated tests. The iteration gate combined the relevant backend/frontend checks.

