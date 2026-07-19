# Iteration 12 – Blueprint / Schema Engine

**Status:** ✅ Complete (Unreleased)  
**Version:** — (critical for dynamic content types)

## Summary

Flat-file definitions of content types and fields, driving dynamic validation and auto-generated admin forms — without a database.

## Delivered

| Deliverable | Status |
|-------------|--------|
| Blueprint files `data/blueprints/{type}.json` | ✅ |
| Built-in `page` + `article` defaults | ✅ |
| Field types: text, textarea, markdown, slug, select, bool, number, email, url, media, datetime | ✅ |
| `DynamicValidator` → shared `Validator` (It.4) | ✅ |
| **`ContentController` save validation via blueprint** | ✅ |
| Admin API `GET/PUT /api/admin/blueprints/*` | ✅ |
| `POST /api/admin/blueprints/{type}/validate` | ✅ |
| Admin UI `/blueprints` + `DynamicForm` preview | ✅ |
| PHPUnit repository, validator, controller, content smoke | ✅ |

## Backend

```
Core/Blueprint/Models/Blueprint.php
Core/Blueprint/Models/FieldDefinition.php
Core/Blueprint/Services/BlueprintRepository.php
Core/Blueprint/Services/DynamicValidator.php
Http/Controllers/Admin/BlueprintController.php
Http/Routes/blueprints.php
Http/Controllers/Content/ContentController.php   # validatePayload → DynamicValidator
```

| Route | Notes |
|-------|-------|
| `GET /api/admin/blueprints` | List summaries |
| `GET /api/admin/blueprints/{type}` | Full schema |
| `PUT /api/admin/blueprints/{type}` | Save (system types stay protected) |
| `POST /api/admin/blueprints/{type}/validate` | Test payload against schema |
| `DELETE /api/admin/blueprints/{type}` | Custom types only |

Storage: `data/blueprints/{type}.json` (falls back to built-in defaults for `page` / `article`).

## Frontend

- `frontend/src/api/blueprint.ts`
- `frontend/src/components/blueprint/DynamicForm.tsx`
- `frontend/src/components/backend/BlueprintManager.tsx` — `/blueprints`

## Out of scope (v1)

- YAML blueprints (JSON only)
- Public/custom content types beyond admin-defined JSON files

## Dependencies (met)

- ✅ Iteration 4 – settings + validation foundation
- ✅ Iteration 19 – content storage abstraction

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 12
- [architecture/API.md](architecture/API.md) – blueprint endpoints
- [STORAGE.md](architecture/STORAGE.md)

## Next

→ [Iteration 13](ITERATION_13.md) – Demo module with isolated mock data
