---
title: Iteration 18 – Admin and Public UI Localization
description: Historical record of the modular SK/EN i18n system and migration of admin, audit, and public UI
icon: material/history
---

# Iteration 18 – Admin and Public UI Localization

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped in waves; plugin i18n remains future scope |
| Release / period | 2.0.44, 2.0.46, 2.0.47, 2.0.49, 2.0.50, hotfix 2.0.51 |
| Record type | historical localization iteration |

## Goal

Remove hardcoded Slovak strings from the admin and public site, introduce backend `Lang.php`, modular SK/EN catalogs, and one frontend `I18nProvider/useI18n` contract.

## Backend

`Support/Lang.php`, `backend/lang/{sk,en}`, `LocaleMiddleware`, `general.language`, and plugin-ready `Lang::addPath()` created the server foundation. Audit messages were re-localized in 2.0.49 through `AuditMessageFormatter` ([ISS-061](ISSUES.md#iss-061)).

## Frontend waves

| Wave | Scope |
|---|---|
| 18a | sidebar and header |
| 18b | list toolbar, pagination, content manager |
| 18c | settings UI and cache panel |
| 18d | translation editor and catalog API |
| 18e | media, navigation, dashboard |
| 18f | comments, messages, backup, trash, logs, platform, and editor |
| Wave 5c | public site, auth flow, helpers |

## Incidents and hotfixes

[ISS-059](ISSUES.md#iss-059) introduced the shared test provider; [ISS-060](ISSUES.md#iss-060) fixed Slovak copy in the English settings catalog; [ISS-062](ISSUES.md#iss-062) completed public i18n. The original text marked ISS-063–070 as pending; the incident register confirms they were fixed in **2.0.51**.

## Historical test counts

The record listed 210 Vitest tests at that wave. This is a historical snapshot and must not be confused with the current 21-step gate.

## Remaining scope

Plugin catalogs under extension namespaces, an optional dynamic i18n endpoint, and a supported-locales registry were not marked complete by the source. The later Hybrid Engine localization branch It.73/76/77 is a separate content-localization project.

