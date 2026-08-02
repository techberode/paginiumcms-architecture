---
title: Iteration 66 – Write-time security gate and test packs
description: Fail-closed validation for untrusted writes and expansion of the 21-step security test gate.
icon: material/history
---

# Iteration 66 – Write-time security gate and test packs

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later fixes, consolidation, and direction changes are identified separately. Current contracts in `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md`, and the Hybrid Engine wave take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.1.0-beta.22 |
| Record type | historical security hardening record |

## Goal

Strengthen security at save/import/CI time without adding heavy checks to anonymous public GET paths.

## Scope and outcome

Code Editor began calling `validateUntrusted` for untrusted paths, which remained fail-closed even when `codePolicy.enabled` was off. The iteration documented the mandatory contract for future 58d shortcode saves.

`run-all-tests.zsh` expanded to 21 steps; `security-regression.sh` and `security-static-grep.sh` covered CodePolicy, XSS/ZIP/headers, outbound hygiene, and frontend security. The operations checklist covered HTTPS, APP_ENV/CORS, and dependency disposition.

## Architecture and security boundaries

The scanner is not a sandbox. The gate runs before write/activation, failures produce no artifact write, and the public path remains deterministic render/cache only. Deferred media re-encoding and HMAC manifest seals were not part of the completed phase.

## Verification and related records

Release: [v2.1.0-beta.22](../CHANGELOG.md#release-2-1-0-beta-22). The source links [ISS-008](ISSUES.md#iss-008), [ISS-014](ISSUES.md#iss-014), and [ISS-089](ISSUES.md#iss-089). Later CI-log secret protection is the separate [ISS-120](ISSUES.md#iss-120), not original It.66 scope.

## Current interpretation

It.66 is the completed write-time/test baseline. Product wiring for shortcode/theme/module surfaces remains in It.67 and must not be marked complete merely because the security helper and test packs exist.
