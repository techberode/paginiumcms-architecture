---
title: Iteration 41 – Email OTP Workflows
description: Historical record of OTP for registration, comment approval, and content publication
icon: material/history
---

# Iteration 41 – Email OTP Workflows

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; hardened later |
| Release / period | release neurčený v zdroji |
| Record type | historical workflow/security iteration |

## Goal

Introduce optional email OTP confirmation for public registration and sensitive editor workflows: comment approval and publication.

## Backend contract

The `workflows` settings group controlled enable flags, TTL, and maximum attempts. `OtpChallengeStore` used flat-file `data/otp-challenges.json` with `flock`; `OtpWorkflowService` delivered codes through the email channel.

The API returned `202` plus `requires_otp` and a challenge ID. Separate verify/resend endpoints existed for auth and admin workflows.

## Frontend and roles

`RegisterModal` gained an OTP step, `OtpConfirmModal` was reused for comments/publication, and API helpers mapped the pending response. Registration was public; comment/publication confirmation required EDITOR, ADMIN, or SUPER_ADMIN.

## Documented limitation and later hardening

Bulk comment approval did not require OTP in the original iteration. This is an explicit historical limitation, not a general rule. Dedicated OTP rate limiting, resend counters, and test isolation followed later; see [ISS-058](ISSUES.md#iss-058) and [ISS-103](ISSUES.md#iss-103).

`debug_code` was allowed only in testing/development when SMTP was unavailable. It must never appear in a production response or CI log.

## Verification

Tests covered the service flow, registration with OTP, and comment approval. The current authentication and 2FA contract is documented in [SECURITY.md](developer/SECURITY.md).

