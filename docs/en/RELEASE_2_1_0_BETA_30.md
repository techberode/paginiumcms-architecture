# Release `v2.1.0-beta.30` — It.74 API keys + JWT

> **Date:** 2026-08-08  
> **Tag:** `v2.1.0-beta.30`  
> **Previous:** [`v2.1.0-beta.29`](../../CHANGELOG.md#release-2-1-0-beta-29)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-30)

## Summary

Ships **Iteration 74 (Hybrid Engine HE-5)** — scoped Bearer API keys for headless integrations, optional short-lived JWT delegation, admin lifecycle UI, and write access via `content:write`. Admin session + CSRF flow is unchanged.

---

## Scope

| Track | Content | Doc |
|-------|---------|-----|
| **It.74a** | Read-only headless API, key store, middleware | [ITERATION_74.md](ITERATION_74.md) |
| **It.74b** | Write scopes, HS256 JWT, rotate/audit | [SECURITY.md](developer/SECURITY.md) §8 |
| **Admin UI** | `/platform/api-keys` | Platform nav (SUPER_ADMIN) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.30
# instance update script, then:
cd frontend && npm ci && npm run build:prod
```

Add to `.env` (project root or `backend/.env`):

```bash
API_KEY_PEPPER=<openssl rand -base64 48>
API_JWT_KEY=<separate openssl rand -base64 48>
```

### Post-deploy checklist

1. **Version** — footer / health reports `2.1.0-beta.30`.
2. **Admin** — Platform → API keys → create read key → copy token once.
3. **Headless** — `curl -H "Authorization: Bearer pgk_…" https://your-site/api/headless/pages/home`.
4. **Guard** — invalid `pgk_*` on `/api/pages/*` returns `401` (no session fallback).

---

## Deferred

- It.72 S3 driver · headless `media:read` route · It.78 upload policy
