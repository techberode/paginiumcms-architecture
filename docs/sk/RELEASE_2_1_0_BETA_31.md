# Release `v2.1.0-beta.31` — API keys UX hardening + It.80 backlog

> **Dátum:** 2026-08-09  
> **Tag:** `v2.1.0-beta.31`  
> **Predchádzajúci:** [`v2.1.0-beta.30`](../../CHANGELOG.md#release-2-1-0-beta-30)  
> **Kanonický changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-31)

## Zhrnutie

Produkčné spevnenie po **It.74** (`beta.30`): ADMIN vidí API kľúče, chýbajúci `API_KEY_PEPPER` sa zobrazí v UI namiesto nejasného `503`, Docker/php-fpm env je spoľahlivejší. Pridaná plánovacia špecifikácia **It.80** (redirecty, 404, webhooks, GDPR, CLI) — implementácia v ďalších beta release.

---

## Rozsah

| Stopa | Obsah | Doc |
|-------|-------|-----|
| **Fix** | API keys navigácia + ACL pre ADMIN | [ITERATION_74](../en/ITERATION_74.md) |
| **Fix** | Probe pepper/JWT + banner v UI | Platform → API kľúče |
| **Fix** | `$_SERVER` fallback pre pepper/JWT | `bootstrap/app.php` |
| **Plán** | It.80 checklist (80a–80g) | [ITERATION_80](ITERATION_80.md) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.31
cd frontend && npm ci && npm run build:prod
```

Po zmene `.env` reštart nestačí — treba recreate PHP:

```bash
./stack.sh up -d --force-recreate php
```

Koreňový `.env` (nie len `backend/.env`, ak root existuje):

```bash
API_KEY_PEPPER=<openssl rand -base64 48>
API_JWT_KEY=<samostatný openssl rand -base64 48>
```

### Post-deploy checklist

1. Verzia `2.1.0-beta.31`.
2. ADMIN — Platform → API kľúče viditeľné.
3. Chýbajúci pepper — banner + Create disabled.
4. Vytvorenie kľúča funguje s pepperom; chyby z API v toast.

---

## Ďalej

- **It.80a** — Redirect manager — cieľ `beta.32`
