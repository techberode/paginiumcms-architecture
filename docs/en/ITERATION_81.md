# Iteration 81 — Editorial workflow & content ops

> **Status:** 🚧 in progress (`81a`–`81e` shipped in `beta.47`; `81f` planned)  
> **Priority:** 🟡 (high editor impact / moderate effort)  
> **Wave:** Product & editorial (post-It.58 shortcodes, post-It.80 ops toolkit)  
> **Depends on:** flat-file content model, existing bulk batch pattern (`BulkBatchResult`), scheduled publishing (It.59), shortcode expander (It.58d)  
> **Snapshot:** 2026-08-15 · after `v2.1.0-beta.47`

## Goal

Ship a **prioritized bundle** of editor-facing features that reduce repetitive work in the admin content UI — duplication, bulk taxonomy, saved list views, publication calendar, stale-content visibility, and reusable snippet blocks — **without** a second content store or SQL.

This iteration is **checklist-driven** (`81a`–`81f`). Sub-phases may ship in separate beta releases (recommended order below).

---

## Code verification (August 2026)

Gaps confirmed directly in the repository before this spec was written:

| # | Feature | Verified in code | Why it helps |
|---|---------|------------------|--------------|
| 1 | Content duplication | `rg "function duplicate\|function clone"` — no controller/service method | Editors writing similar series (e.g. product reviews) restart from scratch instead of “Duplicate as draft” |
| 2 | Editorial calendar | `SchedulerView.tsx` exists — **system job scheduler** (It.29/62), not a publication calendar | No single “what goes live this week” view; teams scan paginated lists and sort by date manually |
| 3 | Bulk tag/category assign | Bulk exists for **delete** + **status** (`ContentController::bulkUpdateContentStatus`); no `bulkAssignTags` | Common need: mark 40 articles `archived` or add a tag after site retagging |
| 4 | Saved filters/views | `rg "SavedFilter\|SavedView"` — nothing | Editors cannot pin “my drafts” or “awaiting review”; filters reset every visit |
| 5 | Stale content flag | Nothing in `PerformanceAuditor` or content index for age/review | SEO and accuracy: outdated pages surface only by accident |
| 6 | Reusable block library | Blueprints = **content-type schemas**, not reusable instances; no `SnippetLibrary` / `ReusableBlock` | Author bio, CTA, newsletter block copied into 20 articles instead of one SSOT reference |

**Existing assets to reuse:** `ContentController` bulk batch loop, `ContentIndexService` (`publishedAt` / `scheduledAt` / `tags`), `PagesManager` filters, `ShortcodeExpanderService`, `PerformanceAuditor` + dashboard widgets (It.71), `BulkOperationLimits` (It.80f).

**Not to confuse:** `/platform/scheduler` → `SchedulerView.tsx` = cron/job queue admin. It.81d adds a **new route/view** (e.g. `/platform/editorial-calendar`) that reads the same content list API, different render.

---

## Priority rationale (impact × realistic effort)

| Order | Sub-phase | Rationale |
|------:|-----------|-----------|
| 1 | **81a** Duplicate content | Single endpoint + service method; largest day-to-day editor time save |
| 2 | **81b** Bulk tag assign | Clone existing bulk-status pattern; tags already on `Content` front matter |
| 3 | **81c** Saved list views | FE-heavy; MVP can use `localStorage` + pinned chips in `PagesManager` |
| 4 | **81d** Editorial calendar | Same data as content list; new calendar/grid component only |
| 5 | **81e** Stale content review | Optional `lastReviewedAt` + dashboard widget; extends audit/index patterns |
| 6 | **81f** Reusable snippet library | New flat-file type + shortcode reference; builds on It.58; largest slice |

---

## Master checklist

| ID | Feature | Priority | Status | Impact / effort | Target release (suggested) |
|----|---------|----------|--------|-----------------|------------------------------|
| **81a** | Duplicate page/article as draft | 🟡 P1 | ✅ done | **High / Low** | `beta.47` |
| **81b** | Bulk assign tags (add/remove/replace) | 🟡 P2 | ✅ done | **High / Low** | `beta.47` |
| **81c** | Saved filters / pinned views | 🟡 P3 | ✅ done | **Med / Low** | `beta.47` |
| **81d** | Editorial calendar (week/month) | 🟡 P4 | ✅ done | **Med / Med** | `beta.47`–`beta.48` |
| **81e** | Stale content flag + dashboard | 🔵 P5 | ✅ done | **Med / Low** | `beta.48` |
| **81f** | Reusable snippet library | 🔵 P6 | ⏳ planned | **High / Med** | `beta.48`+ |

### Status legend

| Symbol | Meaning |
|--------|---------|
| ⏳ planned | Spec agreed; not started |
| 🚧 in progress | Active implementation |
| ✅ done | Shipped in a tagged release |
| ⏸️ deferred | Consciously postponed |

---

## 81a — Duplicate content as draft

### API

| Method | Route | Permission |
|--------|-------|------------|
| `POST` | `/api/pages/{slug}/duplicate` | `content:create` + path ACL on source |
| `POST` | `/api/articles/{slug}/duplicate` | same |

**Request body (optional):**

```json
{
  "newSlug": "my-copy",
  "newTitle": "My copy (draft)"
}
```

**Behaviour:**

- Copy flat-file record (body + front matter) with:
  - `status` → `draft`
  - new unique `slug` (suffix `-copy`, `-copy-2`, … if omitted)
  - `title` suffixed with “ (copy)” if omitted
  - clear `scheduledAt`, `publishApprovedAt`, public SEO `date` optional reset (document choice)
  - preserve tags, layout template, locale variants (It.73) when present
- **Do not copy:** version history as same slug; generate fresh `createdAt` / audit trail entry `content.duplicated`
- Emit hooks: `CONTENT_AFTER_SAVE` (new slug); optional `content.duplicated` for webhooks (It.80d)
- Invalidate cache for new slug only

### Backend

- `ContentDuplicationService` (or method on repository) — single place for copy rules
- Controller delegates; no duplicate logic in route closures
- PHPUnit: slug collision, ACL denied, scheduled fields cleared, hook fired

### Frontend

- Row action + bulk action (optional phase 2): “Duplicate” → toast + navigate to editor for new slug
- i18n `content.duplicate.*` (sk/en)

### Security

- Auth + `PermissionMiddleware('content:create')` + path ACL on **source** read and **target** write path
- CSRF on POST; slug validated (`FileValidator` / existing slug rules)
- Audit log: source slug, new slug, actor (no body in log)

### Definition of Done

- [x] API + tests green
- [x] UI action on pages and articles list
- [ ] CHANGELOG + gate (pending release tag)

---

## 81b — Bulk assign tags

### API

| Method | Route | Permission |
|--------|-------|------------|
| `PATCH` | `/api/pages/bulk-tags` | `content:edit` + per-item path ACL |
| `PATCH` | `/api/articles/bulk-tags` | same |

**Request body:**

```json
{
  "slugs": ["a", "b"],
  "mode": "add",
  "tags": ["archiv", "reviewed"]
}
```

- `mode`: `add` | `remove` | `replace` (default `add`)
- Reuse `BulkBatchResult` + `BulkOperationLimits` cap from It.80f
- Mirror loop structure of `bulkUpdateContentStatus` in `ContentController`
- Cache invalidation per successful slug
- Versioning action `bulk-tags`

**Category note:** Content model today has **`tags` only** (no `category` front matter). MVP = tags. Optional follow-up within 81b: introduce optional `category` string on articles if product taxonomy requires it (single field, not a relational table).

### Frontend

- Bulk toolbar in `PagesManager`: “Add tags…” modal with chip input
- Same for articles manager if separate route

### Definition of Done

- [x] Both content types (pages + articles) where tags apply
- [x] Partial failure reporting in UI (existing bulk pattern)
- [x] Regression tests for invalid tags, ACL, cap exceeded

---

## 81c — Saved filters / pinned views

### MVP (81c.1)

- Persist named filter presets in **`localStorage`** keyed by user id + content type:
  - `status`, `search`, `tag` (when added), `seoIssuesOnly`, sort
- Pin up to N presets (e.g. 5) as chips above content list in `PagesManager`
- Default presets shipped: “All drafts”, “Scheduled”, “Published” (non-destructive, user can hide)

### Optional server-side (81c.2 — defer if MVP sufficient)

- Store presets in user flat-file profile (`data/users/{id}.json` → `contentViews[]`)
- Sync across browsers; requires GET/PATCH `/api/admin/users/me/content-views`

### UX

- “Save current filters…” → name dialog → pinned chip
- Long-press or menu → rename / delete preset

### Definition of Done

- [x] Presets survive reload on same browser
- [x] No PII in preset names logged
- [x] Vitest for serialize/restore helpers

---

## 81d — Editorial calendar

### Scope

- **New admin route:** e.g. `/platform/editorial-calendar` (nav under Content)
- **Not** an extension of `SchedulerView.tsx` (jobs/cron)
- Read existing list/index API with date-oriented filters:
  - `scheduledAt` for scheduled items
  - `date` / `publishedAt` for published
  - `status=draft` with optional `updatedAt` overlay (secondary)

### Views

- **Week** and **month** grid (MVP: month only acceptable)
- Cell/card: title, type (page/article), status badge, click → editor
- Filter: type, author, tag (reuse list query params)

### Backend

- Prefer **no new storage** — extend list endpoint query params if needed (`?calendarFrom=&calendarTo=`) using `ContentIndexService`
- Server-side filter keeps calendar performant for large sites

### Definition of Done

- [x] Calendar shows scheduled + published items in range
- [x] Empty states + loading/error
- [x] i18n sk/en
- [x] No regression to job scheduler route

---

## 81e — Stale content review flag

### Data

- Optional front matter: `lastReviewedAt` (ISO-8601), set manually in editor or via bulk “Mark reviewed today”
- Derived **staleness** (no cron required for MVP):
  - `staleMonths = settings.content.staleReviewMonths` (default `12`, `0` = disabled)
  - Flag when `published` and (`now - coalesce(lastReviewedAt, updatedAt, date)` > threshold)

### Surfaces

- Badge/icon on content list row (“Stale · 14 mo”)
- Dashboard widget (It.71 pattern): count + link to pre-filtered list (`?stale=1`)
- Optional: `PerformanceAuditor` informational issue (non-blocking), not auto-unpublish

### API

- Extend content GET/list payload with `isStale`, `monthsSinceReview` (computed, not stored)
- PATCH accepts `lastReviewedAt` on save (editor “Mark as reviewed” button)

### Definition of Done

- [x] Setting in Settings → Content with default
- [x] Widget + list filter
- [x] Tests for threshold edge cases (missing dates, draft excluded)

---

## 81f — Reusable snippet library

### Concept

- New flat-file content kind: **`snippet`** under `data/snippets/{name}.json` (or `.md` with JSON front matter — align with existing storage conventions)
- Fields: `name`, `title`, `body` (markdown/html), `version`, `enabled`, `updatedAt`
- **Not** a Blueprint (schema); **instances** editors embed by reference

### Reference in content

- Shortcode expander (It.58d): e.g. `[snippet name="author-bio"]` → expand stored body through same sanitizer/expander pipeline
- Admin: `SnippetsManager` (can mirror ShortcodesManager textarea pattern) or extend Shortcodes with `type: snippet` — **pick one store**, document in spec before coding

### Change propagation

- On snippet save → invalidate content cache for all documents referencing that snippet (maintain reverse index in index service or scan on save — document trade-off in implementation PR)

### Security

- Same `CodePolicyEngine` / HTML allow-list as shortcodes
- Mutations: `content:edit` or dedicated `snippets:manage` permission
- No `eval`, no PHP in snippet body

### Definition of Done

- [ ] CRUD admin + seeder for 1–2 bundled snippets (author-bio, cta-banner)
- [ ] Expand at render + preview API
- [ ] Insert panel in editor (reuse shortcode insert UX)
- [ ] Invalidation tests

---

## Cross-cutting requirements

### Security baseline (all sub-phases)

- Mutating routes: `AuthMiddleware` + `PermissionMiddleware` + CSRF
- Bulk ops: `BulkOperationLimits`, ACL per slug, audit via existing content/versioning hooks
- No secrets or full body in logs (`LogSanitizer`)

### No-SQL impact

- All new data stays flat-file or derived index; optional user view prefs in user JSON only (81c.2)
- Snippets = new subdirectory under `data/` (not web-served)

### Non-goals (It.81)

- Full DAM / media library rework (It.72/79)
- Workflow engine (multi-step approval chains) — future iteration
- AI-generated duplicates or auto-rewrite (It.75)
- Replacing Blueprints with snippets (complementary)

### Recommended release slicing

```text
beta.46 → 81a + 81b (duplicate + bulk tags)
beta.47 → 81c + 81d (saved views + calendar MVP)
beta.48 → 81e + 81f (stale flag + snippet library MVP)
```

Adjust only with roadmap + backlog update in both language editions.

---

## Related documents

- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — active priority row for It.81
- [ITERATION_58.md](ITERATION_58.md) — shortcode expander (81f dependency)
- [ITERATION_80.md](ITERATION_80.md) — bulk limits, CLI export/import
- [ITERATION_71.md](ITERATION_71.md) — dashboard / auditor patterns (81e)
