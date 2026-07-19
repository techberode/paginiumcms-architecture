# Iteration 23 – SEO Meta Engine

**Status:** Complete  
**Version:** 2.0.11  
**Release track:** post-2.0.10 (public discoverability — SEO)

## Summary

Automatic meta tags for the public React site: title templates, description, canonical URL, Open Graph, Twitter Card, and JSON-LD — driven by content front matter and admin SEO settings.

## Logical sequence

```
It.19 (published filter) → It.22 (RSS/sitemap) → It.23 (SEO meta on public pages)
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | `Core/Seo/Services/SeoMetaBuilder` | ✅ |
| 2 | Settings group `seo` | ✅ |
| 3 | `GET /api/seo/{type}/{slug}` (public) | ✅ |
| 4 | FE `useSeoMeta` + `<head>` tags in `PublicSiteLayout` | ✅ |
| 5 | PHPUnit + Vitest | ✅ |

---

## Part 1 – Backend SEO builder ✅

**Service:** `backend/app/Core/Seo/Services/SeoMetaBuilder.php`

Builds payload from content + settings:

| Field | Source |
|-------|--------|
| `title` | `seoTitle` / `metaTitle` front matter, else `titleTemplate` |
| `description` | `seoDescription` / `description`, excerpt, body fallback |
| `canonical` | `canonical` front matter, else site URL + path |
| `robots` | `noIndex` front matter, else `robotsDefault` |
| `openGraph` / `twitter` | Derived from title, description, image |
| `jsonLd` | `WebPage` or `Article` schema.org |

**Settings group `seo`:** `titleTemplate`, `defaultDescription`, `defaultImage`, `robotsDefault`, `twitterCard`

---

## Part 2 – Public API ✅

| Method | Route | Auth | Response |
|--------|-------|------|----------|
| GET | `/api/seo/{type}/{slug}` | Public (published only) | `{ success, data: SeoMeta }` |

- `type`: `page` | `article`
- Draft/unpublished content returns **404** for anonymous visitors; editors with session see meta for preview.

**Route file:** `backend/app/Http/Routes/seo.php`

---

## Part 3 – Frontend head tags ✅

**Hook:** `frontend/src/hooks/useSeoMeta.ts`

- Fetches `/api/seo/{type}/{slug}` when public route resolves a page or article
- Applies `document.title`, meta description/robots, canonical link, OG/Twitter tags, JSON-LD script (`#paginium-json-ld`)

**Integration:** `PublicSiteLayout.tsx` — derives `type`/`slug` from `currentDoc` (home, slug pages, blog articles).

---

## Tests ✅

| Suite | File |
|-------|------|
| PHPUnit | `backend/tests/Core/Seo/Services/SeoMetaBuilderTest.php` |
| PHPUnit | `backend/tests/Http/Controllers/Seo/SeoControllerTest.php` |
| Vitest | `frontend/src/hooks/useSeoMeta.test.ts` |

---

## Deploy notes

No nginx changes required — SEO uses existing `/api/*` proxy. After deploy, verify `<head>` on a published page (View Source or DevTools → Elements).

**Release gate:** PHPUnit + PHPStan L8 + Vitest green + CHANGELOG `[2.0.11]`.

## Related

- [ITERATION_22.md](ITERATION_22.md) — feeds/sitemap (discoverability)
- [ITERATION_27.md](ITERATION_27.md) — admin SEO panel (builds on this backend)
- [ROADMAP.md](ROADMAP.md) — iteration map (It.9 = prototype port; SEO engine = It.23)
- [architecture/CONTENT_API.md](architecture/CONTENT_API.md) — `/api/seo`, search
- [CHANGELOG.md](../CHANGELOG.md) — `[2.0.11]` release notes
