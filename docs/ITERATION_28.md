# Iteration 28 – Bulk Actions (Platform)

**Status:** Complete  
**Version:** 2.0.16  
**Release track:** Admin multi-select + batch API

## Summary

Unified **bulk selection** and **batch operations** across admin modules. Extracts the Media Library pattern into reusable frontend primitives and adds backend batch endpoints with a consistent `{ processed, succeeded, failed, results[] }` response shape.

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | `useBulkSelection` hook — toggle, select-all, clear, prune on filter change | ✅ |
| 2 | `BulkActionBar` — sticky action bar for selected items | ✅ |
| 3 | `BulkBatchResult` (BE) + `BulkBatchResult` type (FE) | ✅ |
| 4 | **Media** — refactor to shared bulk UI (existing `POST /api/media/bulk-delete`) | ✅ |
| 5 | **Pages / Articles** — bulk delete + bulk status (publish/draft/archive) | ✅ |
| 6 | **Trash** — bulk restore | ✅ |
| 7 | **Comments** — bulk approve / reject / delete | ✅ |
| 8 | **Users** — bulk delete (self + last super-admin guard) | ✅ |
| 9 | **Backups** — bulk delete/restore, import ZIP, download + SHA-256 verify | ✅ |
| 10 | Tests (PHPUnit + Vitest) + docs | ✅ |

## Deferred (post 2.0.16)

| Feature | Notes |
|---------|--------|
| Bulk SEO patch | Builds on It.27 front matter fields |
| Messages bulk mark-read | Lower priority |
| Generic `BulkActionRegistry` | It.30 contextual actions |

---

## Part 1 – Shared frontend

| File | Role |
|------|------|
| `frontend/src/hooks/useBulkSelection.ts` | Selection state keyed by visible item ids |
| `frontend/src/components/backend/BulkActionBar.tsx` | Action buttons when `count > 0` |
| `frontend/src/types/bulk.ts` | `BulkBatchResult` + `summarizeBulkResult()` |

**MediaManager** refactored to use shared components + select-all in list table.

---

## Part 2 – Backend batch API

### Response contract

```json
{
  "processed": 3,
  "succeeded": 2,
  "failed": 1,
  "results": [
    { "id": "slug-a", "ok": true },
    { "id": "slug-b", "ok": true },
    { "id": "missing", "ok": false, "error": "Content not found" }
  ]
}
```

### Endpoints

| Method | Route | ACL | Body |
|--------|-------|-----|------|
| POST | `/api/pages/bulk-delete` | `content:delete` | `{ slugs: string[] }` |
| PATCH | `/api/pages/bulk-status` | `content:edit` | `{ slugs, status }` |
| POST | `/api/articles/bulk-delete` | `content:delete` | `{ slugs }` |
| PATCH | `/api/articles/bulk-status` | `content:edit` | `{ slugs, status }` |
| POST | `/api/admin/trash/bulk-restore` | EDITOR+2FA | `{ ids: string[] }` |
| POST | `/api/admin/comments/bulk-status` | ADMIN+2FA | `{ ids, status }` |
| POST | `/api/admin/comments/bulk-delete` | ADMIN+2FA | `{ ids }` |
| POST | `/api/admin/users/bulk-delete` | ADMIN+2FA | `{ ids }` |
| POST | `/api/admin/backups/import` | ADMIN+2FA | multipart `file`, optional `name` |
| POST | `/api/admin/backups/bulk-delete` | ADMIN+2FA | `{ ids }` |
| POST | `/api/admin/backups/bulk-restore` | ADMIN+2FA | `{ ids }` |
| GET | `/api/admin/backups/{id}/verify` | ADMIN+2FA | SHA-256 integrity check |

Existing: `POST /api/media/bulk-delete` (unchanged response `{ deleted: number }`).

**Backup integrity:** new backups store `sha256` in metadata; download response includes `X-Backup-SHA256` header.

### Backend files

| File | Change |
|------|--------|
| `Http/Support/BulkBatchResult.php` | Per-item success/failure aggregator |
| `ContentController.php` | `bulkDeleteContent`, `bulkUpdateContentStatus` |
| `TrashController.php` | `bulkRestore` |
| `CommentsController.php` | `bulkUpdateStatus`, `bulkDelete` |

---

## Part 3 – Admin UI wiring

| Module | Bulk actions |
|--------|----------------|
| Media Library | Delete selected (+ select-all in list) |
| Pages / Articles | Publish, Draft, Archive, Delete |
| Trash | Restore selected |
| Comments | Approve, Reject, Delete |
| Users | Delete selected (cannot select self) |
| Backups | Restore / Delete selected; import ZIP; per-row Download + Verify hash |

---

## Test plan

1. Media list → select multiple → Delete selected.
2. Articles table → select rows → Publish → status badges update.
3. Trash → select 2 items → bulk restore (partial failure if path exists).
4. Comments → select pending → Approve.
5. PHPUnit: `BulkBatchResultTest`, `ContentControllerTest` bulk, `TrashControllerTest` bulk restore.
6. Vitest: `useBulkSelection.test.ts`.

---

## Related documents

- [ITERATION_27.md](ITERATION_27.md) — list modes + SEO (selection deferred to It.28)
- [ITERATION_24.md](ITERATION_24.md) — media bulk delete origin
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — It.29+
