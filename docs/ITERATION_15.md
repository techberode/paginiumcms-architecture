# Iteration 15 – External Plugins & Runtime

**Status:** ✅ Complete  
**Version:** **2.0.38**

## Summary

Plugin system with code **outside Core**: ZIP import, flat-file registry, hook-based integration, enabled extension routes, and admin UI.

## Architectural law

```
backend/app/Http/Extensions/{id}/     ← PHP plugin code
backend/app/Http/Routes/extensions/ ← plugin routes (enabled only)
frontend/src/extensions/{id}/       ← React plugin UI bundles
```

**Never** place plugin code in `Core/`.

## Deliverables

| Area | Component | Status |
|------|-----------|--------|
| Registry | `data/plugins.json` + `PluginRegistry` (flock) | ✅ |
| Import | `PluginImporter` + `PluginPolicyScanner` + `CodePolicyEngine` | ✅ |
| Runtime | `PluginManager` — list, import, enable, disable, uninstall | ✅ |
| Hooks | `HookManager` in DI + `bootEnabledExtensions()` | ✅ |
| Routes | Bootstrap loads `Http/Routes/extensions/{id}.php` for enabled plugins | ✅ |
| API | `GET/POST /api/admin/extensions`, enable/disable, delete | ✅ |
| FE | `extensionsApi`, `ExtensionsManager`, `/extensions`, sidebar | ✅ |
| FE loader | `frontend/src/extensions/loader.ts` (`import.meta.glob`) | ✅ |

## Admin API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/admin/extensions` | List discovered + registry state |
| POST | `/api/admin/extensions/import` | Upload ZIP (field `file`) |
| PUT | `/api/admin/extensions/{id}/enable` | Enable + register hooks |
| PUT | `/api/admin/extensions/{id}/disable` | Disable + unregister hooks |
| DELETE | `/api/admin/extensions/{id}` | Uninstall (registry + files) |

## ZIP layout

```text
hello-widget/
├── plugin.json          # required: id, name, version
├── src/                 # PHP (policy-scanned)
├── routes.php           # optional → Http/Routes/extensions/{id}.php
└── frontend/            # optional → frontend/src/extensions/{id}/
```

## Tests

- `PluginRegistryTest`, `PluginManagerTest`, `PluginImporterTest`
- `ExtensionsControllerTest`
- `frontend/src/extensions/loader.test.ts`

## Gate

```bash
./scripts/iteration-gate.sh
```

## Next

→ [Post-15 wave It.53–58](ITERATION_WAVE_POST_15.md) (editor profiles, navigation, layout builder)  
→ [Iteration 16](ITERATION_16.md) — Code Editor plugin bundles
