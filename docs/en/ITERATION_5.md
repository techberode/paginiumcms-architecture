---
title: Iteration 5 – Users and Authentication Hardening
description: Historical record of user administration, session authentication, roles, and 2FA rules
icon: material/history
---

# Iteration 5 – Users and Authentication Hardening

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; repeatedly hardened later |
| Release / period | 2.0.6 foundation; UI 2.0.18; confirmation 2.0.56 |
| Record type | historical security iteration |

## Goal

Deliver user administration, the `USER/EDITOR/ADMIN/SUPER_ADMIN` roles, HttpOnly cookie sessions, and 2FA enforcement for administrative routes.

## Backend and API

- `UserController` – create, update, deactivate, and assign roles;
- `AuthenticationManager` + `SessionManager` – cookie session, no Bearer token in the SPA;
- `TwoFactorMiddleware` – protection for staff/admin operations;
- `CsrfProtectionManager` and later the global synchronizer-token middleware;
- `PasswordPolicy` and shared validation.

The historical login/register endpoint used a legacy flat envelope with `user` at the root. This distinction remains documented in [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Admin user form

The form included `username`, `name`, `email`, `password`, `passwordConfirm`, `role`, `active`, and 2FA state. Password was mandatory on create and optional on edit. Since 2.0.56, backend and frontend require matching password confirmation.

The historical API exposed the 2FA secret in a detailed admin flow. The current security contract minimizes secret exposure and prohibits logging it.

## Later hardening

Login loops, 2FA provisioning, `twoFactorVerifiedAt` persistence, session keepalive, trusted proxies, and rate-limit regressions were handled in [ISSUES.md](ISSUES.md#iss-025), [ISS-029](ISSUES.md#iss-029) through [ISS-034](ISSUES.md#iss-034), and later security hotfixes.

