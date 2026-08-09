# Release `v2.1.0-beta.31` — API keys UX hardening + It.80 backlog

> **Date:** 2026-08-09  
> **Tag:** `v2.1.0-beta.31`  
> **Previous:** [`v2.1.0-beta.30`](../../CHANGELOG.md#release-2-1-0-beta-30)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-31)

## Summary

Production-hardening follow-up to **It.74** after `beta.30` deploy feedback: ADMIN users can reach API keys, missing `API_KEY_PEPPER` is surfaced in UI instead of opaque `503`, and Docker/php-fpm env resolution is more reliable. Adds **Iteration 80** planning spec (redirects, 404 ops, webhooks, GDPR, CLI) — implementation starts in subsequent betas.

---

## Scope

| Track | Content | Doc |
|-------|---------|-----|
| **Fix** | API keys nav + ACL for ADMIN | [ITERATION_74.md](ITERATION_74.md) |
| **Fix** | Pepper/JWT config probe + UI banner | Platform → API keys |
| **Fix** | `$_SERVER` env fallback for pepper/JWT keys | `bootstrap/app.php` |
| **Plan** | It.80 checklist (80a–80g) | [ITERATION_80.md](ITERATION_80.md) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.31
# instance update script, then:
cd frontend && npm ci && npm run build:prod
```

**Important:** after changing `.env`, recreate PHP container — `docker compose restart` does **not** reload `env_file`:

```bash
./stack.sh up -d --force-recreate php
```

Ensure project **root** `.env` contains (not only `backend/.env` when root file exists):

```bash
API_KEY_PEPPER=<openssl rand -base64 48>
API_JWT_KEY=<separate openssl rand -base64 48>
```

### Post-deploy checklist

1. **Version** — footer / health reports `2.1.0-beta.31`.
2. **ADMIN nav** — Platform → API keys visible (not SUPER_ADMIN-only).
3. **Missing pepper** — banner explains fix; Create disabled until configured.
4. **Create key** — succeeds when pepper present; API error text shown on failure.

---

## Next

- **It.80a** — Redirect manager (301/302) — target `beta.32` slice
