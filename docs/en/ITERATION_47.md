---
title: Iteration 47 – Notification connector credentials
description: Bearer/Basic authentication for ntfy and a safe connector test endpoint.
icon: material/history
---

# Iteration 47 – Notification connector credentials

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Implemented; original exact release not confirmed |
| Release / period | pôvodný target 2.0.25 |
| Record type | historical security delivery record |

## Goal

Complete missing connector authentication, especially Bearer token or Basic auth for private and self-hosted ntfy topics, and add a connection test that does not create a fake incident.

## Scope and outcome

The `connectors` settings group added `none|token|basic` mode, ntfy token, username/password, and an optional webhook authentication header name. `NtfyAdapter` and `NotificationFactory` would build the correct headers; admin API exposed connector testing and authentication status.

The frontend added password fields, authentication mode, a test action, and a status badge. Tests covered Bearer, Basic, and no-auth branches.

## Architecture and security boundaries

The test endpoint must be rate-limited and audited without secret values. Credentials must not enter plaintext logs; the later security baseline includes encryption at rest through `EncryptionService`. Outbound URLs remain subject to SSRF protection.

## Verification and related records

The current register records ntfy authentication as fixed in [ISS-013](ISSUES.md#iss-013). The source says “Implemented (Unreleased)” with a 2.0.25 target, so this document does not prove the exact first release tag.

## Current interpretation

It.47 is the completed connector-authentication baseline. Additional providers must reuse the same secret, outbound, rate-limit, and audit contract rather than introducing parallel ad-hoc paths.
