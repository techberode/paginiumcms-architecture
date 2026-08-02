---
title: Iteration 48 – PHP front-matter templates and static/dynamic web
description: Plan for templates, metadata formats, and deterministic static public-site builds.
icon: material/history
---

# Iteration 48 – PHP front-matter templates and static/dynamic web

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ⏳ Planned |
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

It.48 remains the target static-build layer. It must align with the It.58 layout AST, It.69 cache invalidation, and It.70 publish states; Save, Build, Git publish, and Deploy remain separate actions.
