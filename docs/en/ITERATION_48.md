---
title: Iteration 48 – PHP front-matter templates and static/dynamic web
description: Plan for templates, metadata formats, and deterministic static public-site builds.
icon: material/history
---

# Iteration 48 – PHP front-matter templates and static/dynamic web

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped — **48a** compile/cache + **48b** public HTML serve |
| Release / period | bez samostatného release |
| Record type | historical rendering and publishing design |

## Goal

Support custom public templates, YAML/JSON/INI metadata, and a selectable `dynamic|static|hybrid` public mode while keeping the admin as a React SPA.

## Scope and outcome

The design introduced `PhpTemplateRenderer`, `StaticSiteGenerator`, a metadata resolver, and `static:rebuild-page|all` jobs. Published Markdown/JSON content would remain the SSOT while generated HTML under `storage/static/` was derived output only.

Admin UI would expose render mode, a Monaco template editor, build progress, and fresh/stale status. Nginx would serve the static tree while leaving `/admin`, `/api`, and interactive hybrid routes dynamic.

## Architecture and security boundaries

PHP templates may only be allow-listed artifacts, with no `eval`, no arbitrary filesystem access, and mandatory syntax/policy checks. Static output must not be PHP-executable. Sanitization and CSP apply to build output as well.

## Verification and related records

The source is a plan, not implementation evidence. The later documentation plan requires It.48 to be designed together with the Git publishing pipeline in [It.70](ITERATION_70.md) to avoid competing publish queues.

## Current interpretation

**48a (this tree):** `engine.renderMode` (`dynamic` default / `hybrid` / `static`), `StaticSiteCompiler` + `StaticSiteGenerator` write derived HTML under `storage/app/static/` (never `.php`). Jobs `static.rebuild`. Admin `GET/POST /api/admin/static/*` with `static:rebuild`. Auto-compile after content save when mode is hybrid/static. Git publish queue is untouched.

**48b (this tree):** PHP serves allow-listed `index.html` at `GET /static-html/pages/{slug}` and `GET /static-html/blog/{slug}` when `renderMode` is `hybrid` or `static`. Dynamic mode and reserved slugs (`login`, `dashboard`, `api`, …) return 404. `/storage/*` still never serves the compiled tree. Optional nginx snippet `docs/deploy/nginx-static-html.conf` maps `/` and pretty URLs to those endpoints and falls back to the SPA; `/api`, `/admin` first-segments, and `/assets` stay dynamic. Include the snippet only after switching off Classic/dynamic — otherwise every public GET pays an extra PHP 404.

It.48 must keep aligning with the It.58 layout AST, It.69 cache invalidation, and It.70 publish states; Save, Build, Git publish, and Deploy remain separate actions.
