# Release `v2.1.0-beta.47` — Editorial workflow (It.81a–81e)

> **Date:** 2026-08-15  
> **Tag:** `v2.1.0-beta.47`  
> **Iteration:** It.81a–81e (editorial workflow & content ops)  
> **Spec:** [ITERATION_81.md](ITERATION_81.md)

---

## One-line summary

Editors get **duplicate-as-draft**, **bulk tag updates**, **saved list views**, a **publication calendar**, and **stale-content review** — all on the existing flat-file index, without SQL.

---

## What's new for editors

| Slice | Admin surface | Notes |
|-------|---------------|-------|
| **81a** Duplicate | Pages/Articles list → row action **Duplicate** | New draft copy; schedule fields cleared; hook `content.duplicated` |
| **81b** Bulk tags | Bulk toolbar → **Tags…** | Modes add / remove / replace; max 100 slugs per request |
| **81c** Saved views | Chips above list → **Save current filters…** | `localStorage` per user + type; 5 custom views max |
| **81d** Editorial calendar | **Workspace → Editorial calendar** | Month grid at `/platform/editorial-calendar`; not the cron scheduler |
| **81e** Stale content | List badge, filter **Stale only**, dashboard widget | Setting `content.staleReviewMonths` (default 12); **Mark reviewed today** in editor |

---

## API additions

| Method | Route | Permission |
|--------|-------|------------|
| `POST` | `/api/pages/{slug}/duplicate` | `content:create` |
| `POST` | `/api/articles/{slug}/duplicate` | `content:create` |
| `PATCH` | `/api/pages/bulk-tags` | `content:edit` |
| `PATCH` | `/api/articles/bulk-tags` | `content:edit` |
| `GET` | `/api/admin/content/editorial-calendar?from=&to=` | `content:edit` |

List endpoints accept `?stale=1` and return computed `isStale`, `monthsSinceReview`, `lastReviewedAt`.

---

## Settings

**Settings → Content → Stale review threshold (months)** (`content.staleReviewMonths`)

- Default: **12**
- `0` = stale flag disabled

---

## Production deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.47 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

After deploy:

1. Hard refresh admin (Ctrl+Shift+R).
2. **Pages** → duplicate a draft; confirm new slug opens in editor.
3. **Editorial calendar** in sidebar; month navigation loads scheduled/published items.
4. **Dashboard** → stale content count links to `/pages?stale=1`.
5. Optional: set stale threshold in Settings → Content.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green on tag commit
- [ ] Duplicate creates draft; scheduled fields cleared on copy
- [ ] Bulk tags modal updates selected rows
- [ ] Saved view chip survives reload (same browser/user)
- [ ] Calendar distinct from `/scheduler` (jobs)
- [ ] Stale badge only on published items past threshold

---

## Links

- [CHANGELOG — beta.47](../../CHANGELOG.md#release-2-1-0-beta-47)
- [ITERATION_81.md](ITERATION_81.md)
- Remaining: **81f** reusable snippet library (planned `beta.48+`)
