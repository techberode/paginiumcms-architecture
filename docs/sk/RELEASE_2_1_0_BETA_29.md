# Release `v2.1.0-beta.29` — It.72 + It.73 multi-locale

> **Dátum:** 2026-08-08  
> **Tag:** `v2.1.0-beta.29`  
> **Predchádzajúci:** [`v2.1.0-beta.28`](../../CHANGELOG.md#release-2-1-0-beta-28)  
> **Kanonický changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-29)  
> **Incidenty:** [ISSUES.md](../ISSUES.md) ISS-134, ISS-135

## Zhrnutie

Release obsahuje **It.72 (media storage drivers MVP)**, **It.73 (viacjazyčný obsah — HE-6 hotové)**, **HTTP test izoláciu (ISS-134)**, **react-router 7.18.2 (ISS-089/117)** a **fix analytics dedupe testu**.

Legacy single-locale obsah zostáva čitateľný bez migrácie. Voliteľný batch upgrade: `content:locale-migrate`.

---

## Rozsah

| Stopa | Obsah | Doc |
|-------|-------|-----|
| **It.72** | Local media driver, factory, settings probe | [ITERATION_72.md](ITERATION_72.md) |
| **It.73** | Read/write/publish/migrate + editor SK/EN tabs | [ITERATION_73.md](ITERATION_73.md) |
| **API** | Kanonický locale kontrakt §15 | [CONTENT_API.md](architecture/CONTENT_API.md) |
| **CI** | ISS-134 HTTP izolácia, FeedGenerator test fix | [TESTING.md](developer/TESTING.md) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.29
# update skript inštancie, potom:
cd frontend && npm ci && npm run build:prod
```

### Post-deploy checklist

1. **Verzia** — footer / health hlási `2.1.0-beta.29`.
2. **Editor** — otvor stránku → SK/EN tabs → ulož scoped locale.
3. **Public API** — `GET /api/pages/home?locale=en` vracia `_locale` metadata.
4. **Voliteľná migrácia** — `php backend/bin/console content:locale-migrate dry-run --default-locale=sk` pred `run --yes`.

---

## Odložené

- It.72 S3 driver · It.76 translation Apply · It.73 migration resume · [ISS-135](../ISSUES.md#iss-135) shortcode allowlist
