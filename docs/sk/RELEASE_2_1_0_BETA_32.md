# Release `v2.1.0-beta.32` — It.80a Redirect manager

> **Dátum:** 2026-08-09  
> **Tag:** `v2.1.0-beta.32`  
> **Predchádzajúci:** [`v2.1.0-beta.31`](../../CHANGELOG.md#release-2-1-0-beta-31)  
> **Kanonický changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-32)

## Zhrnutie

**It.80a** — flat-file HTTP redirect pravidlá (301/302), admin CRUD UI, PHP middleware a verejný resolve endpoint. Oprava API barrel pre moduly `apiKeys` a `redirects`.

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.32
cd frontend && npm ci && npm run build:prod
```

### Checklist

1. Verzia `2.1.0-beta.32`.
2. Admin — Platform → Redirecty.
3. Resolve — `GET /api/public/redirect-resolve?path=/old`.
4. Produkčný nginx — voliteľný hook pred SPA (It.80 doc).

---

## Ďalej

- **It.80b** — 404 tracking report
