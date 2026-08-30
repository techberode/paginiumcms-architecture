# Iteration 87 — Project site planner & editorial UX completion

> **Status:** ⏳ planned — **first post-stable product slice** (after It.25 + `v2.2.0` stable tag)  
> **Priority:** 🟡 **P1** (Project Site Planner — Full CMS); 🟡 **P2** (UX audit deferrals from It.86d)  
> **Wave:** Editorial product (extends It.81d editorial calendar, contrasts with It.82 Origin Panel)  
> **Depends on:** It.25 setup/onboarding baseline, It.81 content index + editorial calendar, flat-file SSOT, RBAC  
> **Snapshot:** 2026-08-30 · spec from frontend audit + product request (Full version)

## Goal

Deliver three complementary tracks in one iteration:

1. **Track A — UX audit completion** (`87a`–`87d`): finish deferred admin/public polish from the 2026-08-30 frontend audit (It.86d).
2. **Track B — Project Site Planner** (`87e`–`87j`): a **customer-facing admin module** where site owners plan launches, content milestones, and publication deadlines — with progress, on-time / late / early variance — inspired by Origin Panel clarity but **for editors and project owners**, shipped in the **Full CMS** (not maintainer-gated).
3. **Track C — Theme static JS (allow-list)** (`87k`–`87m`): **optional, security-first** path to ship **explicitly declared** `.js` files from `themes/{id}/assets/` only — with **SRI** and **CSP hash/nonce**; not a blanket “allow all scripts in HTML/ZIP”.

---

## Origin Panel vs Editorial Calendar vs Project Site Planner

| Module | Audience | Gating | Data source | Purpose |
|--------|----------|--------|-------------|---------|
| **Origin Panel** (It.82) | Maintainer / SUPER_ADMIN | `ORIGIN_PANEL=true`; excluded from customer archives | `project-catalog.json` + dev probes | Code iteration progress, deploy badges, wiring health |
| **Editorial calendar** (It.81d) | Editors | Standard admin (Content permission) | Existing content `scheduledAt` / `publishedAt` | **Reactive** — what is already scheduled or published |
| **Project Site Planner** (It.87) | Editors, admins, project owners | **Full CMS default** — no env gate | Dedicated flat-file plan store + optional links to content | **Proactive** — plan milestones before content exists; track slip/ahead |

```text
Editorial calendar  = “What does the CMS already know will go live?”
Project planner     = “What does the team intend to ship, by when, and are we on track?”
Origin Panel        = “What did the maintainer ship in code, and is prod on the right tag?”
```

---

## Priority rationale

| Order | Slice | Priority | Rationale |
|------:|-------|----------|-----------|
| 1 | **87e** Plan data model | 🟡 P1 | SSOT for planner; blocks all UI |
| 2 | **87f** Plan CRUD API + RBAC | 🟡 P1 | Backend truth before dashboard |
| 3 | **87g** Planner dashboard UI | 🟡 P1 | Core user-visible value for Full CMS |
| 4 | **87h** Deadlines by content type | 🟡 P1 | “Publish 5 articles by March” use case |
| 5 | **87i** Content linking + auto-status | 🟡 P2 | Reuse It.81 index; reduce double entry |
| 6 | **87j** Overview widgets (overdue / upcoming) | 🔵 P2 | Dashboard KPI row + admin home |
| 7 | **87b** Skeleton loaders | 🟡 P2 | High perceived quality on slow lists |
| 8 | **87a** Responsive `srcset` | 🟡 P2 | Public performance + audit closure |
| 9 | **87c** Empty states | 🔵 P3 | First-run delight; pairs with It.25 |
| 10 | **87d** Getting-started tour | 🟡 P2 | Overlaps It.25 — coordinate, do not duplicate wizard |
| 11 | **87k** Theme `assets/` JS allow-list | 🔵 P3 | Extends It.67/83; fail-closed manifest |
| 12 | **87l** SRI hashes at import/activate | 🔵 P3 | Integrity before any script is eligible |
| 13 | **87m** CSP hash/nonce for theme scripts | 🔵 P3 | No `'unsafe-inline'`; hashes in CSP header |

**Recommended delivery order:**

```text
87e → 87f → 87g → 87h → 87i → 87j → 87b → 87a → 87c → 87d → 87k → 87l → 87m
```

Ship **87e–87h** as MVP for beta.61; **87i–87j** and UX track can follow in the same iteration or a patch. **Track C (`87k`–`87m`)** is an explicit security decision — ship only after planner MVP or as a focused patch; never “open JS everywhere”.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **87a** | Responsive `srcset` on public content images | 🟡 P2 | ⏳ planned | Audit deferral; `BlogRenderer` / media helpers |
| **87b** | Skeleton loading states (admin lists) | 🟡 P2 | ⏳ planned | Pages, messages, comments, media — consistent pattern |
| **87c** | Empty states (“Create first article”) | 🔵 P3 | ⏳ planned | List + dashboard zero-data UX |
| **87d** | Getting-started / onboarding tour | 🟡 P2 | ⏳ planned | Coordinate with [It.25](ITERATION_25.md); checklist-driven, not blocking wizard |
| **87e** | Plan flat-file schema + repository | 🟡 P1 | ⏳ planned | `data/project-plans/` SSOT |
| **87f** | Planner API + permissions | 🟡 P1 | ⏳ planned | CRUD, CSRF, AuthZ |
| **87g** | **Project Planner panel** (admin UI) | 🟡 P1 | ⏳ planned | Progress bars, timeline, variance badges |
| **87h** | Milestones & content-type deadlines | 🟡 P1 | ⏳ planned | page / article / landing / media / custom |
| **87i** | Link plan items ↔ content + sync status | 🟡 P2 | ⏳ planned | Optional slug binding; auto-`done` on publish |
| **87j** | Dashboard widgets (overdue, this week) | 🔵 P2 | ⏳ planned | Admin home KPI strip |
| **87k** | Theme `assets/` JS allow-list + manifest | 🔵 P3 | ⏳ planned | Only declared paths; import policy scan |
| **87l** | SRI (`integrity`) at import/activate | 🔵 P3 | ⏳ planned | SHA-384 stored in theme manifest |
| **87m** | CSP hash/nonce for theme scripts | 🔵 P3 | ⏳ planned | `script-src` hashes; dedicated static route |

---

## Track B — Project Site Planner (spec)

### User stories

1. As a **site owner**, I create a **project plan** for a relaunch with phases (Discovery → Content → Launch) and due dates.
2. As an **editor**, I add tasks like “Homepage refresh (page)” and “Launch blog post (article)” with target dates before writing content.
3. As a **team lead**, I open the **Planner panel** and see overall **progress %**, items **overdue**, **due this week**, and items finished **early** or **late**.
4. As an **editor**, I link a plan item to an existing draft; when the article is **published**, the plan item moves to **done** automatically.
5. As an **admin**, I grant `project-plan:read` / `project-plan:manage` without giving Origin Panel access.

### Data model (flat-file, no SQL)

**Store:** `data/project-plans/{planId}.json` (multiple plans) or single default plan in MVP — prefer **multiple plans** with one marked `isDefault`.

**Plan document (schema `project-plan@1`):**

```json
{
  "schemaVersion": 1,
  "id": "site-relaunch-2026",
  "title": "Corporate site relaunch",
  "description": "Q4 marketing launch",
  "timezone": "Europe/Bratislava",
  "createdAt": "2026-08-30T10:00:00+02:00",
  "updatedAt": "2026-08-30T12:00:00+02:00",
  "createdBy": "user-uuid",
  "phases": [
    { "id": "phase-1", "title": "Content draft", "sortOrder": 1 }
  ],
  "items": [
    {
      "id": "item-1",
      "phaseId": "phase-1",
      "title": "Launch announcement",
      "contentType": "article",
      "dueAt": "2026-09-15T09:00:00+02:00",
      "status": "planned",
      "linkedContent": { "type": "article", "slug": null },
      "completedAt": null,
      "notes": ""
    }
  ]
}
```

**Item `status` enum:** `planned` | `in_progress` | `done` | `skipped` | `blocked`

**Content types (`contentType`):** `page` | `article` | `landing` | `media` | `newsletter` | `custom`

**Variance (computed, not stored):**

| Condition | Badge |
|-----------|--------|
| `done` && `completedAt` < `dueAt` | **Early** (N days) |
| `done` && `completedAt` > `dueAt` | **Late** (N days) |
| `done` && same calendar day | **On time** |
| not `done` && now > `dueAt` | **Overdue** (N days) |
| not `done` && due within 7 days | **Due soon** |

**Progress %:** `weight(status)` per item — same rule as Origin catalog: done=100%, in_progress=50%, planned/blocked=0%, skipped excluded from denominator.

### API (admin)

| Method | Route | Permission | Notes |
|--------|-------|------------|-------|
| `GET` | `/api/admin/project-plans` | `project-plan:read` | List plans + summary progress |
| `GET` | `/api/admin/project-plans/{id}` | `project-plan:read` | Full plan + computed variance |
| `POST` | `/api/admin/project-plans` | `project-plan:manage` | Create plan |
| `PATCH` | `/api/admin/project-plans/{id}` | `project-plan:manage` | Update metadata / phases |
| `POST` | `/api/admin/project-plans/{id}/items` | `project-plan:manage` | Add item |
| `PATCH` | `/api/admin/project-plans/{id}/items/{itemId}` | `project-plan:manage` | Update item / status |
| `DELETE` | `/api/admin/project-plans/{id}/items/{itemId}` | `project-plan:manage` | Remove item |
| `GET` | `/api/admin/project-plans/overview` | `project-plan:read` | Aggregated KPIs for dashboard widgets |

Auth: standard session + CSRF on mutating routes. **No** `ORIGIN_PANEL` gate.

**Public settings:** optional `projectPlanner.enabled` (default `true` in Full profile; `false` only via explicit settings override for stripped demos).

### Admin UI

| Route | Component | Notes |
|-------|-----------|-------|
| `/platform/project-planner` | `ProjectPlannerView.tsx` | Main cockpit — mirrors Origin **layout language** (progress header, iteration-style cards) but editor copy |
| `/platform/project-planner/{planId}` | `ProjectPlanDetailView.tsx` | Phase groups, item table, add-item modal |
| Admin home (optional 87j) | `ProjectPlannerSummaryWidget.tsx` | Overdue count, % complete, next 3 deadlines |

**Nav:** Workspace section, near Editorial calendar — label i18n `projectPlanner.nav`.

**Visual patterns to reuse:** `ProgressBar` from `OriginPanelView`, deploy-style badges repurposed as variance badges (`on_time`, `early`, `late`, `overdue`).

### Integration with editorial calendar (It.81d)

| Planner | Editorial calendar |
|---------|-------------------|
| Plan item with future `dueAt`, no content yet | Not visible (no slug) |
| Plan item linked to draft with `scheduledAt` | Calendar shows scheduled date; planner shows link icon |
| Content published | Planner item → `done`, `completedAt` = `publishedAt` |

Optional read-only overlay in `EditorialCalendarView`: ghost entries for plan items with `dueAt` but no content (dashed border) — **87i** stretch goal.

### Security baseline

- Path traversal: plan IDs validated (`^[a-z0-9-]+$`), files only under `data/project-plans/`.
- RBAC: new permissions registered in default ADMIN role; SUPER_ADMIN always allowed.
- Audit log: plan create/update/delete with `LogSanitizer` on titles.
- No SSRF / outbound calls — local flat-file only.

### Backend modules (suggested layout)

```text
backend/app/Modules/ProjectPlanner/
  Models/ProjectPlan.php
  Repositories/ProjectPlanRepository.php
  Services/ProjectPlanProgressService.php
  Services/ProjectPlanContentSyncService.php   # 87i
Http/Controllers/ProjectPlanner/ProjectPlanController.php
Http/Routes/project-planner.php                # auto-discovery
frontend/src/api/projectPlanner.ts
frontend/src/components/backend/ProjectPlannerView.tsx
frontend/src/i18n/modules/projectPlanner/{en,sk}.ts
```

### Tests (minimum)

- `ProjectPlanRepositoryTest` — CRUD, schema validation, path guard
- `ProjectPlanProgressServiceTest` — percent + variance edge cases (timezone, same-day)
- `ProjectPlanControllerTest` — auth, permission, CSRF
- `ProjectPlanContentSyncServiceTest` — publish hook marks linked item done
- FE Vitest: variance label helper, progress bar props

---

## Track A — UX audit (`87a`–`87d`)

Deferred from [CHANGELOG Unreleased](CHANGELOG.md) / It.86 item `86d`:

| ID | Item | Primary files | DoD hint |
|----|------|---------------|----------|
| **87a** | `srcset` / responsive images | `BlogRenderer.tsx`, public media helpers | At least hero + inline article images |
| **87b** | Skeletons | `PagesManager`, `MessagesViewer`, `CommentsManager`, `MediaLibrary` | Shared `ListSkeleton` component |
| **87c** | Empty states | Content lists, dashboard | i18n + CTA button to create |
| **87d** | Onboarding tour | New `OnboardingTour.tsx` or extend It.25 wizard | 5–7 steps; dismiss + “don’t show again” in user prefs |

---

## Track C — Theme static JS allow-list (`87k`–`87m`)

> **Not shipped today.** PaginiumCMS themes are HTML/CSS + build-time `PublicShell`; arbitrary `.js` from ZIP or content is intentionally blocked ([architecture/THEMES.md](architecture/THEMES.md) §9, It.67). Track C adds a **narrow, opt-in** exception — not “import any HTML template with scripts”.

### Design principles (fail-closed)

| Rule | Meaning |
|------|---------|
| **Allow-list only** | JS must be listed in `theme.json` → `assets.scripts[]`; files only under `assets/*.js` |
| **No inline scripts** | Templates/partials/content still strip `<script>`; no `'unsafe-inline'` in CSP |
| **No remote URLs** | Same as today — manifest rejects `https://…/*.js` |
| **Import scan** | `UntrustedPolicyScanner` + new rules: max size, banned tokens (`eval`, `document.cookie`, …) |
| **Serve from dedicated route** | e.g. `GET /theme-assets/{themeId}/{file}.js` — not web-root arbitrary paths |
| **Integrity required** | Script tags emitted only with matching SRI + CSP `sha384-…` hash |
| **Active theme only** | Inactive/uninstalled themes’ JS is not web-reachable |

### Manifest extension (example)

```json
{
  "id": "clean-journal",
  "assets": {
    "scripts": [
      {
        "path": "assets/nav-toggle.js",
        "integrity": "sha384-…",
        "load": "defer"
      }
    ]
  }
}
```

- **`integrity`** computed at **import** and re-verified on **activate** (file on disk must match hash).
- Optional setting: `appearance.themeScriptsEnabled` (default **false** until admin explicitly enables for the site).

### Slice breakdown

| ID | Deliverable | Security notes |
|----|-------------|----------------|
| **87k** | Allow-list schema + import copy of `assets/*.js` + policy rules | Reject `.js` outside `assets/`; reject if not listed in manifest; extend `ThemeImporterTest` |
| **87l** | SRI hash generation + storage in manifest/registry | On import: hash file → write manifest; on activate: `hash_equals` file vs manifest |
| **87m** | Public render: `ThemeScriptLoader` + CSP header augmentation | `PublicShell` injects `<script src="…" integrity="…" crossorigin="anonymous">`; `SecurityMiddleware` adds script hashes to CSP (or per-request nonce scoped to theme route only) |

### Explicit non-goals (Track C)

- User-uploaded `.js` in media library
- `<script>` in Markdown/HTML content (unless separate, audited `allowScriptTags` flow — unchanged)
- Runtime loading of arbitrary React bundles from ZIP
- Third-party CDN script tags
- PHP in theme packages beyond existing It.67 policy

### Tests (minimum)

- Import theme with undeclared `assets/evil.js` → **rejected**
- Import with declared script + tampered file on activate → **rejected**
- Public page with `themeScriptsEnabled=false` → **no** theme script tags
- CSP header contains expected hash; inline script fixture → **blocked** in browser regression doc

### Related

- [ITERATION_67.md](ITERATION_67.md) — untrusted surfaces baseline
- [ITERATION_83.md](ITERATION_83.md) — theme runtime
- [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md)

---

## Distribution (Full CMS vs Origin)

| Aspect | Origin Panel | Project Site Planner |
|--------|--------------|----------------------|
| Customer archive | **Excluded** ([ORIGIN_PANEL_PACKAGING.md](ORIGIN_PANEL_PACKAGING.md)) | **Included** in Full CMS |
| Env gate | `ORIGIN_PANEL=true` required | None (RBAC only) |
| Default permissions | SUPER_ADMIN only | ADMIN + configurable |
| SSOT | `docs/manifest/*.json` | `data/project-plans/*.json` |

Document in a short **`PROJECT_PLANNER.md`** (or admin guide section) that the planner is a **product module**, not a maintainer tool.

---

## Definition of Done (iteration)

### MVP (87e–87h)

- [ ] Create/edit/delete plans and items via API with tests
- [ ] `/platform/project-planner` shows progress %, phase list, variance badges
- [ ] At least one content-type template (article + page) in add-item flow
- [ ] `./scripts/iteration-gate.sh` green
- [ ] Admin guide section (EN) + SK mirror
- [ ] CHANGELOG + `project-catalog.json` it.87 probes when shipped

### Full iteration (+ 87i–87j, 87a–87d)

- [ ] Content publish sync + dashboard widget
- [ ] UX audit slices closed or explicitly deferred with ISS entry
- [ ] RBAC regression test for new permissions

### Optional Track C (87k–87m) — theme static JS

- [ ] Manifest allow-list + import policy tests
- [ ] SRI verified on activate
- [ ] CSP hash/nonce on public shell; admin toggle default off
- [ ] SECURITY_ISSUES / ISSUES entry if scope expands beyond allow-list

---

## Related documents

- [ITERATION_81.md](ITERATION_81.md) — editorial calendar (81d)
- [ITERATION_82.md](ITERATION_82.md) — Origin Panel (maintainer)
- [ITERATION_25.md](ITERATION_25.md) — setup wizard / onboarding
- [ITERATION_83.md](ITERATION_83.md) — theme runtime
- [architecture/THEMES.md](architecture/THEMES.md) — theme security §9; Track C target
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — priority row
- [manifest/project-catalog.json](../manifest/project-catalog.json) — it.87 progress SSOT (Origin mirrors only)
