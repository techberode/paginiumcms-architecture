# Release `v2.1.0-beta.29` — It.72 + It.73 multi-locale

> **Date:** 2026-08-08  
> **Tag:** `v2.1.0-beta.29`  
> **Previous:** [`v2.1.0-beta.28`](../../CHANGELOG.md#release-2-1-0-beta-28)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-29)  
> **Incidents:** [ISSUES.md](../ISSUES.md) ISS-134, ISS-135

## Summary

Ships **Iteration 72 (media storage drivers MVP)**, **Iteration 73 (multi-locale content — HE-6 complete)**, **HTTP test isolation (ISS-134)**, **react-router 7.18.2 (ISS-089/117)**, and the **analytics dedupe test fix**.

Legacy single-locale content remains readable without migration. Optional batch upgrade: `content:locale-migrate`.

---

## Scope

| Track | Content | Doc |
|-------|---------|-----|
| **It.72** | Local media driver, factory, settings probe | [ITERATION_72.md](ITERATION_72.md) |
| **It.73** | Read/write/publish/migrate + editor SK/EN tabs | [ITERATION_73.md](ITERATION_73.md) |
| **API** | Canonical locale contract §15 | [CONTENT_API.md](architecture/CONTENT_API.md) |
| **CI** | ISS-134 HTTP isolation, FeedGenerator test fix | [TESTING.md](developer/TESTING.md) |

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.29
# instance update script, then:
cd frontend && npm ci && npm run build:prod
```

### Post-deploy checklist

1. **Version** — footer / health reports `2.1.0-beta.29`.
2. **Editor** — open a page → SK/EN tabs → save scoped locale.
3. **Public API** — `GET /api/pages/home?_locale=en` returns `_locale` metadata.
4. **Optional migration** — `php backend/bin/console content:locale-migrate dry-run --default-locale=sk` before `run --yes`.

---

## Deferred

- It.72 S3 driver · It.76 translation Apply · It.73 migration resume · [ISS-135](../ISSUES.md#iss-135) shortcode allowlist
