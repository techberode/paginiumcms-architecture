# Origin manifest SSOT

Machine-readable progress for the **Origin Panel** (It.82e). Do not duplicate iteration state in prose docs without updating these files.

## Files

| File | Role |
|------|------|
| [`project-catalog.json`](project-catalog.json) | Iteration roadmap, item phases, optional `probeId`, `since` / `targetVersion` for deploy badges |
| [`implementation-checklist.json`](implementation-checklist.json) | Operator slices, issue links, gate/deploy checklist rows (mirrors recent work) |

## Automation flow

```text
project-catalog.json
        +
FeatureProbeRegistry (runtime wiring checks)
        ↓
ProjectCatalogMergeService + CatalogDeployStatusResolver
        ↓
GET /api/admin/origin/overview → Origin Panel UI
        ↓
Origin Panel: progress %, deploy badges, release slices (checklist.json)
```

- **Progress %** — weighted item scores; items with `probeId` use live probe status (`implemented` = 100%, `partial` = 50%).
- **Deploy badge** — compares `AppVersion::current()` to iteration `since` or `targetVersion`:
  - `live` — running version ≥ tag and iteration complete
  - `pending_deploy` — catalog expects a newer tag than this instance
  - `unreleased` / `in_progress` — code on disk, no production tag yet
- **Validation** — `./scripts/validate-project-catalog.sh` (also run from `iteration-gate.sh`).

## When you ship a slice

1. Add/update iteration + items in `project-catalog.json`.
2. Add probes under `backend/app/Modules/Origin/Probes/` and register in `FeatureProbeRegistry`.
3. Add i18n keys in `frontend/src/i18n/modules/origin/{en,sk}.ts`.
4. Mirror operator steps in `implementation-checklist.json` and `docs/en/CHECKLIST.md`.
5. Set `since` or `targetVersion` when tagging; after production deploy, Origin shows **live** automatically.

## Customer packaging

Both manifest files are **excluded** from downstream customer archives (see [ORIGIN_PANEL_PACKAGING.md](../en/ORIGIN_PANEL_PACKAGING.md)).
