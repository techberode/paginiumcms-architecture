---
title: Iteration 4 – Settings Engine, Error Handler and Shared Validation
description: Historical record of flat-file settings, centralized API errors, and shared validation rules
icon: material/history
---

# Iteration 4 – Settings Engine, Error Handler and Shared Validation

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.6 – základ jadra; neskoršie doplnenie 2.0.56 |
| Record type | historical foundation iteration |

## Goal

Create a database-free schema-driven Settings Engine, a centralized JSON error handler, and validation rules reusable by backend and frontend forms.

## Delivered foundation

| Component | Responsibility |
|---|---|
| `SettingsSchema.php` | Groups and field definitions |
| `SettingsRepository.php` | `data/settings.json`, delta storage, `flock` |
| `SettingsController.php` | Admin CRUD, public slice, and reset |
| `Validator.php` / `ValidationRules.php` | Stateless validation and rule catalog |
| `ValidationController.php` | `GET /api/validation/rules` |
| `ApiErrorHandler.php` | Exception mapping to stable JSON responses |

## API and frontend

The foundation distinguishes `422` validation failures with `errors`, generic `4xx/5xx` failures, and a public settings slice. The frontend uses `settings.ts`, `validation.ts`, `SettingsView`, `SettingsContext`, `useSettings`, and a validation mirror.

Release 2.0.56 added coordinated `passwordConfirm` validation; this was a later extension of the original iteration.

## Security note

Secret fields are not public settings and must follow the current encryption contract when stored. Current precedence, migration, and reload rules are documented in [SETTINGS.md](architecture/SETTINGS.md).

