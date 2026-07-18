# Iteration 10 – XML Feeds (RSS & Sitemap)

**Status:** In progress (delivered via [Iteration 22](ITERATION_22.md))  
**Version:** 2.0.10 (target)

## Summary

Public RSS feed and XML sitemap generated from the flat-file content index, with admin settings for feed metadata and inclusion rules.

## Goals

| Deliverable | Description |
|-------------|-------------|
| RSS | `GET /feed.xml` – articles/pages with pubDate, excerpt, link |
| Sitemap | `GET /sitemap.xml` – published URLs from content index |
| Settings | Admin group `feeds`: title, description, items limit, include pages/articles |
| Cache | Optional file cache for generated XML (reuse `ContentCacheService`) |

## Proposed backend

```
Core/Feeds/
├── Services/FeedGenerator.php
├── Services/SitemapGenerator.php
└── Config/services.php

Http/Controllers/Feeds/FeedController.php
Http/Routes/feeds.php
```

## Proposed frontend

- Settings section in `SettingsView` (feeds group)
- Public site `<link rel="alternate">` in `PublicSiteLayout`

## Dependencies

- ✅ Iteration 19 – content index + published filter
- ✅ Iteration 23 – SEO meta on public pages (was planned under an older “It.9 SEO” label)

## Tests (planned)

- `FeedGeneratorTest` – only published content, valid XML
- HTTP smoke – `GET /feed.xml`, `GET /sitemap.xml`

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 10
- [CONTENT_API.md](architecture/CONTENT_API.md) – published filter rules

## Implementation track

RSS + sitemap are implemented in **[Iteration 22](ITERATION_22.md)** (after It. 21 API contract).  
See It. 22 Part 3 for final file layout and routes.

## Next

→ [Iteration 22](ITERATION_22.md) – feeds (this scope)  
→ [Iteration 11](ITERATION_11.md) – SSO and fine-grained ACL
