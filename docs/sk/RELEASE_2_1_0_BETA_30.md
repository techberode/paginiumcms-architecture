# Release `v2.1.0-beta.30` — It.74 API kľúče + JWT

> **Dátum:** 2026-08-08  
> **Tag:** `v2.1.0-beta.30`  
> **Predchádzajúci:** [`v2.1.0-beta.29`](../../CHANGELOG.md#release-2-1-0-beta-29)  
> **Kanonický changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-30)

## Zhrnutie

**Iterácia 74 (Hybrid Engine HE-5)** — scoped Bearer API kľúče pre headless integrácie, voliteľné krátko žijúce JWT, admin UI pre lifecycle kľúčov a zápis cez `content:write`. Admin session + CSRF zostáva bez zmeny.

---

## Rozsah

| Stopa | Obsah | Doc |
|-------|-------|-----|
| **It.74a** | Read-only headless API, key store, middleware | [ITERATION_74.md](ITERATION_74.md) |
| **It.74b** | Write scopes, HS256 JWT, rotate/audit | [SECURITY.md](developer/SECURITY.md) §8 |
| **Admin UI** | `/platform/api-keys` | Platform nav (SUPER_ADMIN) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.30
# instance update script, potom:
cd frontend && npm ci && npm run build:prod
```

Do `.env` (koreň projektu alebo `backend/.env`):

```bash
API_KEY_PEPPER=<openssl rand -base64 48>
API_JWT_KEY=<samostatný openssl rand -base64 48>
```

### Post-deploy checklist

1. **Verzia** — footer / health hlási `2.1.0-beta.30`.
2. **Admin** — Platform → API kľúče → vytvoriť read key → token skopírovať raz.
3. **Headless** — `curl -H "Authorization: Bearer pgk_…" https://vas-web/api/headless/pages/home`.
4. **Guard** — neplatný `pgk_*` na `/api/pages/*` vráti `401` (bez session fallbacku).

---

## Odložené

- It.72 S3 driver · headless `media:read` route · It.78 upload policy
