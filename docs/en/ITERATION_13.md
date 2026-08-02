---
title: Iteration 13 – Isolated Demo Sandbox
description: Historical record of demo mode, isolated storage, resets, quick login, and the marketing trial flow
icon: material/history
---

# Iteration 13 – Isolated Demo Sandbox

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete across multiple waves |
| Release / period | 2.0.28 až v2.1.0-beta.11 |
| Record type | historical product and operations iteration |

## Product position

The demo module is intended only for the project-owned `demo.paginiumcms.com` instance. It is not part of the standard customer production profile. Production and demo data, secrets, storage, ports, logs, and schedulers must remain isolated.

## Delivered scope

- `DEMO_MODE` and fail-closed production checks;
- `DemoStorageService` and separate `backend/storage/app/demo/`;
- seeded content, user, settings, navigation, comments/messages/newsletter;
- periodic reset through `demo:reset-if-due`;
- public demo strip, countdown, and admin onboarding;
- `GET /api/demo/public-info`, `POST /api/demo/quick-login`, admin status/reset;
- quick login without publishing a password in public settings;
- isolation and PHPUnit smoke tests.

## Security correction

The original record contained static demo credentials. V4 replaced them with a server-side quick-login flow, and [ISS-100](ISSUES.md#iss-100) removed the password from `GET /api/settings/public`. The canonical documentation therefore does not repeat a static password; current onboarding uses quick login.

## Operations

Demo bootstrap must create a writable storage tree; [ISS-099](ISSUES.md#iss-099) and [ISS-102](ISSUES.md#iss-102) document permission and missing-directory incidents. The production deployment procedure is in [DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md).

## Boundary

Resetting demo data must never touch the production SSOT. The service must fail when the demo path overlaps the production content path.

