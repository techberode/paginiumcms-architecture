# Iteration 17 – API↔Frontend Scaffold Law

**Status:** Partial  
**Version:** 2.0.9 (barrel + `content.ts` fixed in It. 21)

## Summary

Every backend endpoint must have a typed frontend client, a consuming component/route, and a documentation entry. This iteration defines the **law** and tracks migration away from raw `useApi` calls.

## The law

```
endpoint → api/*.ts → component/route → API.md / API_CONTRACT.md
```

## Done ✅

- Typed modules: `auth.ts`, `media.ts`, `settings.ts`, `locks.ts`, `drafts.ts`, …
- `backup.ts`, `audit.ts`, `codeEditor.ts` – admin API clients
- **2.0.9:** `content.ts`, `user.ts`; fixed `api/index.ts` barrel
- [API_CONTRACT.md](architecture/API_CONTRACT.md) – response shapes

## Remaining ⏳

| Item | Description |
|------|-------------|
| Replace raw `useApi` | Migrate `PagesManager`, `DashboardView`, etc. to typed clients |
| `CONTRIBUTING.md` | Checklist for new endpoints |
| Code Editor wizard | “New extension” scaffold generator |
| Full `API.md` refresh | Endpoint inventory at 2.0.9+ state |

## Files to migrate (priority)

- `components/backend/PagesManager.tsx` → `contentApi.list()`
- `context/ContentContext.tsx` → `contentApi`
- Remaining `get('/api/...')` in dashboard components

## Dependencies

- 🟡 Iteration 21 – contract tests lock response shapes
- Required before **Iteration 15** (plugins)

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 17
- [API_CONTRACT.md](architecture/API_CONTRACT.md)

## Next

→ [Iteration 18](ITERATION_18.md) – admin UI i18n migration
