# Iteration 12 – Blueprint / Schema Engine

**Status:** Planned  
**Version:** — (critical for dynamic content types)

## Summary

Flat-file definitions of content types and fields, driving dynamic validation and auto-generated admin forms — without a database.

## Goals

| Deliverable | Description |
|-------------|-------------|
| Blueprint files | JSON/YAML in `data/blueprints/{type}.json` |
| Field types | text, markdown, select, media, relation, datetime, … |
| Validation | Runtime rules from blueprint → `Validator` integration |
| Admin UI | Dynamic form renderer from schema |
| API | CRUD blueprints (admin only) |

## Proposed structure

```
Core/Blueprint/
├── Models/Blueprint.php
├── Models/FieldDefinition.php
├── Services/BlueprintRepository.php
└── Services/DynamicValidator.php

Http/Controllers/Admin/BlueprintController.php
frontend/src/components/blueprint/DynamicForm.tsx
```

## Dependencies

- ✅ Iteration 4 – settings + validation foundation
- ✅ Iteration 19 – content storage abstraction
- Design approval required before implementation (see ROADMAP)

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 12
- [STORAGE.md](architecture/STORAGE.md)

## Next

→ [Iteration 13](ITERATION_13.md) – Demo module with isolated mock data
