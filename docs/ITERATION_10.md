# Iteration 10 – XML Feeds (RSS & Sitemap)

**Status:** ✅ Complete  
**Release:** 2.0.10 (core) + Unreleased polish (cache, robots.txt, head links)

## Summary

Public RSS feed and XML sitemap generated from the flat-file content index, with admin settings for feed metadata and inclusion rules. Scope was originally planned here; core implementation landed in **It. 22** (2.0.10). Polish (cache, robots, discoverability) closed in It.10 follow-up.

## Delivered

| Deliverable | Status |
|-------------|--------|
| RSS `GET /feed.xml` | ✅ |
| Sitemap `GET /sitemap.xml` | ✅ |
| `GET /robots.txt` with `Sitemap:` directive | ✅ |
| Settings group `feeds` (title, description, limits, include pages/articles) | ✅ |
| Public `<link rel="alternate">` + `<link rel="sitemap">` in site layout | ✅ |
| File cache for generated XML (`ContentCacheService`) | ✅ |
| Cache invalidation on content publish | ✅ |
| PHPUnit + Postman smoke for feed routes | ✅ |

## Backend

```
Core/Feeds/Services/FeedGenerator.php
Core/Feeds/Services/SitemapGenerator.php
Core/Feeds/Services/RobotsTxtGenerator.php
Core/Cache/ContentCacheService.php   # rememberFeedRss/Sitemap/Robots + invalidateFeeds()
Http/Controllers/Feeds/FeedController.php
Http/Routes/feeds.php                # /feed.xml, /sitemap.xml, /robots.txt
```

| Route | Content-Type | Cache TTL |
|-------|--------------|-----------|
| `GET /feed.xml` | `application/rss+xml` | 300s (ContentCacheService) |
| `GET /sitemap.xml` | `application/xml` | 300s |
| `GET /robots.txt` | `text/plain` | 300s |

**Invalidation:** `ContentCacheService::invalidatePage()` / `invalidateArticle()` / `purgeAll()` bump `content.feeds.gen`.

## Frontend

- `PublicSiteLayout.tsx` — RSS alternate + sitemap `<link>` when `feeds.enabled`
- `vite.config.ts` — dev/preview proxy for `/feed.xml`, `/sitemap.xml`, `/robots.txt`

## Tests

| Suite | File |
|-------|------|
| PHPUnit | `FeedGeneratorTest`, `RobotsTxtGeneratorTest` |
| PHPUnit | `FeedControllerTest` — RSS, sitemap, robots HTTP smoke |
| Postman | `docs/api/PaginiumCMS.postman_collection.json` — folder **Public Feeds** |

## Dependencies (met)

- ✅ Iteration 19 – content index + published filter
- ✅ Iteration 22 – initial ship (2.0.10)
- ✅ Iteration 23 – SEO meta on public pages

## Related docs

- [ITERATION_22.md](ITERATION_22.md) – release that shipped core It.10 scope
- [ROADMAP.md](ROADMAP.md) – Iteration 10
- [CHANGELOG.md](../CHANGELOG.md) – `[2.0.10]` + `[Unreleased]`
- [deploy/NGINX_API.md](deploy/NGINX_API.md) – proxy feed routes before SPA fallback

## Next

→ [Iteration 11](ITERATION_11.md) – SSO and fine-grained ACL
