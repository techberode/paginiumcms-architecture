# Release `v2.1.0-beta.32` — It.80a Redirect manager

> **Date:** 2026-08-09  
> **Tag:** `v2.1.0-beta.32`  
> **Previous:** [`v2.1.0-beta.31`](../../CHANGELOG.md#release-2-1-0-beta-31)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-32)

## Summary

Ships **Iteration 80a** — flat-file HTTP redirect rules (301/302), admin CRUD UI, PHP middleware, and public resolve endpoint. Fixes API barrel registration for `apiKeys` and `redirects` modules.

---

## Scope

| Track | Content | Doc |
|-------|---------|-----|
| **It.80a** | `RedirectStore`, middleware, admin API/UI | [ITERATION_80.md](ITERATION_80.md) |
| **Fix** | `frontend/src/api/index.ts` — `apiKeys` + `redirects` barrel | Wave 5e lint |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.32
cd frontend && npm ci && npm run build:prod
```

### Post-deploy checklist

1. **Version** — `2.1.0-beta.32`.
2. **Admin** — Platform → Redirects → create rule `/old` → `/new`.
3. **PHP paths** — `GET /api/public/redirect-resolve?path=/old` returns `301` + `Location`.
4. **Production nginx** — slug redirects need optional nginx hook before SPA fallback (see It.80 doc).

---

## Next

- **It.80b** — 404 tracking report
