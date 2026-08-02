---
title: Iteration 12 – Blueprint and Schema Engine
description: Historical record of flat-file content definitions, dynamic validation, and generated forms
icon: material/history
---

# Iteration 12 – Blueprint and Schema Engine

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; first standalone release is not identified by the source |
| Release / period | po 2.0.27, pred aktuálnym beta snapshotom |
| Record type | historical architecture iteration |

## Goal

Define content types and fields through flat-file blueprints, use them for backend validation, and generate admin forms without a database.

## Delivered scope

| Element | Status |
|---|---|
| `data/blueprints/{type}.json` | authoritative custom-type definition |
| Built-in `page` and `article` | fallback definitions |
| Field types | text, textarea, markdown, slug, select, bool, number, email, url, media, datetime |
| Validation | `DynamicValidator` into the shared `Validator` |
| Content save | `ContentController` validates payload through the blueprint |
| Admin API/UI | list/show/save/validate/delete custom types plus `DynamicForm` preview |

## v1 boundaries

The source confirms JSON blueprints and admin-defined schemas; YAML and fully public custom content types were outside v1. The source does not identify a standalone first-release tag, so this documentation does not invent one.

## Relationships

Blueprints build on It.4 validation and the storage layer. Current SSOT, schema migration, and atomic-write rules in [STORAGE.md](architecture/STORAGE.md) take precedence.

