# Project Site Planner

> **Status:** 87a–87m shipped (store + API + panel + linking + dashboard + UX + theme JS allow-list)  
> **Audience:** Full CMS (editors, admins, project owners) — **not** Origin Panel  
> **SSOT:** `data/project-plans/{planId}.json` under the content storage root

The planner is a **product module**. Origin Panel remains maintainer-gated (`ORIGIN_PANEL=true`) and is excluded from customer archives. This store is included in the Full CMS.

## What 87e provides

Kuchár (PHP) owns the plan documents. Čašník (Slim API) is wired. Hosť (React panel) is **87g** (`/platform/project-planner`).

| Piece | Location |
|-------|----------|
| Schema type | `project-plan@1` in `DocumentSchemaRegistry` |
| Domain gate | `ProjectPlanDocumentValidator` (enums, IDs, timezone, phase references) |
| IDs | `^[a-z0-9-]+$`, max 64 chars — files only under `data/project-plans/` |
| Repository | `ProjectPlanRepository` |
| Progress / variance | `ProjectPlanProgressService` (computed, not stored) |

## Document shape

See the example in [ITERATION_87.md](../ITERATION_87.md) Track B. Extra fields on disk:

- `isDefault` (bool) — at most one plan is default; saving a new default clears the flag on others
- `createdBy` (string, optional)

**Item status:** `planned` | `in_progress` | `done` | `skipped` | `blocked`  
**Content types:** `page` | `article` | `landing` | `media` | `newsletter` | `custom`

## Variance (computed)

Calendar-day comparisons use the plan `timezone`. Overdue uses the due instant (`now > dueAt`). Skipped items are excluded from progress % (same weight rule as Origin: done=100%, in_progress=50%, planned/blocked=0%).

## Admin API (87f)

Auth session + `PermissionMiddleware`. Mutating methods use global CSRF (`X-CSRF-TOKEN`). No `ORIGIN_PANEL` gate.

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/admin/project-plans` | `project-plan:read` |
| GET | `/api/admin/project-plans/overview` | `project-plan:read` |
| GET | `/api/admin/project-plans/{id}` | `project-plan:read` |
| POST | `/api/admin/project-plans` | `project-plan:manage` |
| PATCH | `/api/admin/project-plans/{id}` | `project-plan:manage` |
| POST | `/api/admin/project-plans/{id}/items` | `project-plan:manage` |
| PATCH | `/api/admin/project-plans/{id}/items/{itemId}` | `project-plan:manage` |
| DELETE | `/api/admin/project-plans/{id}/items/{itemId}` | `project-plan:manage` |

`project-plan:manage` implies `project-plan:read`. Default ADMIN and EDITOR both get manage. Setting `projectPlanner.enabled=false` returns 404.

Frontend client: `frontend/src/api/projectPlanner.ts`.

## Admin UI (87g)

| Route | Component |
|-------|-----------|
| `/platform/project-planner` | `ProjectPlannerView.tsx` — KPI header, upcoming deadlines, plan cards |
| `/platform/project-planner/{planId}` | `ProjectPlanDetailView.tsx` — phases, item table, add-item templates + milestone pack |

Workspace nav (next to editorial calendar). Hidden when `projectPlanner.enabled=false`. Guide: [user/PROJECT_PLANNER.md](../user/PROJECT_PLANNER.md).

## Next slices

- **87g** — `/platform/project-planner` panel ✅
- **87h** — content-type deadline templates ✅
- **87i–87j** — content linking + dashboard widgets
