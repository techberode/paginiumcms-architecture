# Iteration 10 – XML Feeds (RSS & Sitemap)

**Status:** 🟡 **Core shipped** (2.0.10 via [Iteration 22](ITERATION_22.md)); polish pending before It.11  
**Release:** 2.0.10 (backend); remaining items → Unreleased polish

## Summary

Public RSS feed and XML sitemap generated from the flat-file content index, with admin settings for feed metadata and inclusion rules. Scope was originally planned here; implementation landed in **It. 22** together with trash UI and brute-force lockout.

## Delivered

| Deliverable | Status |
|-------------|--------|
| RSS `GET /feed.xml` | ✅ |
| Sitemap `GET /sitemap.xml` | ✅ |
| Settings group `feeds` (title, description, limits, include pages/articles) | ✅ |
| Public `<link rel="alternate">` in site layout | ✅ |
| File cache for generated XML (`ContentCacheService`) | ⏳ | Planned in It.22; not wired in `FeedController` yet |

## Remaining before It.11 {#remaining-before-it11}

| Gap | Status | Notes |
|-----|--------|-------|
| `ContentCacheService` for RSS/sitemap XML | ⏳ | `FeedController` regenerates on every request |
| `GET /robots.txt` with `Sitemap:` directive | ⏳ | Crawler discoverability |
| Sitemap link in public `<head>` | ⏳ | RSS alternate exists in `PublicSiteLayout` |
| Cache invalidation on content publish | ⏳ | Hook into `ContentCacheService::invalidate*` |
| Newman smoke for `/feed.xml` + `/sitemap.xml` | ⏳ | See [ITERATION_22.md](ITERATION_22.md) |

## Backend (as shipped)

```
Core/Feeds/Services/FeedGenerator.php
Core/Feeds/Services/SitemapGenerator.php
Http/Controllers/Feeds/FeedController.php
Http/Routes/feeds.php
```

## Tests

- `FeedGeneratorTest` – published content only, valid XML
- HTTP smoke – `GET /feed.xml`, `GET /sitemap.xml` (via application / contract tests)

## Dependencies (met)

- ✅ Iteration 19 – content index + published filter
- ✅ Iteration 23 – SEO meta on public pages

## Related docs

- [ITERATION_22.md](ITERATION_22.md) – release that shipped It. 10 scope
- [ROADMAP.md](ROADMAP.md) – Iteration 10
- [CHANGELOG.md](../CHANGELOG.md) – `[2.0.10]`

## Next

→ **Finish remaining items above**, then [Iteration 11](ITERATION_11.md) – SSO and fine-grained ACL
