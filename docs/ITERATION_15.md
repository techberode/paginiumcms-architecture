# Iteration 15 – External Plugins & Runtime

**Status:** Planned  
**Version:** — (depends on It. 14 + It. 21)

## Summary

Plugin system with code **outside Core**: install/import/enable/disable via flat-file registry and hook-based integration.

## Architectural law

```
backend/app/Http/Extensions/{id}/     ← PHP plugin code
backend/app/Http/Routes/extensions/ ← plugin routes
frontend/src/extensions/{id}/       ← React plugin UI
```

**Never** place plugin code in `Core/`.

## Goals

| Deliverable | Description |
|-------------|-------------|
| `PluginManager` | install, import, enable, disable |
| Registry | `data/plugins.json` (flat-file) |
| Import validation | `CodePolicyEngine` gate before activation |
| `HookManager` | DI-registered hooks for core events |
| FE loader | Dynamic import of extension bundles |

## Dependencies

- ✅ Iteration 14 – `CodePolicyEngine`
- 🟡 Iteration 17 – API↔FE scaffold law
- 🟡 Iteration 21 – stable API contract

## Related docs

- [PLUGINS.md](architecture/PLUGINS.md)
- [ROADMAP.md](ROADMAP.md) – Iteration 15
- [CODING_STANDARDS.md](developer/CODING_STANDARDS.md)

## Next

→ [Iteration 16](ITERATION_16.md) – full Code Editor stack + themes
