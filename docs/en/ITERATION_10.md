---
title: Iteration 10 – XML Feeds, Sitemap and robots.txt
description: Historical record of RSS, sitemap, robots.txt, caching, and public discoverability
icon: material/history
---

# Iteration 10 – XML Feeds, Sitemap and robots.txt

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | core v 2.0.10 (It.22); polish v neskorších releasoch |
| Record type | historical feature iteration |

## Goal and timeline

The scope was originally planned as It.10, the core shipped in It.22/release 2.0.10, and later polish added caching, `robots.txt`, and discoverability links. The original “Unreleased polish” label is no longer the current state.

## Delivered scope

| Route | Content-Type | Historical TTL |
|---|---|---|
| `GET /feed.xml` | `application/rss+xml` | 300 s |
| `GET /sitemap.xml` | `application/xml` | 300 s |
| `GET /robots.txt` | `text/plain` | 300 s |

`FeedGenerator`, `SitemapGenerator`, `RobotsTxtGenerator`, `ContentCacheService`, the controller, and routes generate output from the published flat-file index. Content changes invalidate feed generations.

## Frontend and settings

The `feeds` settings control metadata and inclusion rules. The public layout adds RSS alternate and sitemap links; the Vite dev/preview proxy recognizes all three public routes.

## Verification

PHPUnit covers generators and HTTP smoke; the Postman collection includes a Public Feeds folder. Nginx must handle feed routes before the SPA fallback.

