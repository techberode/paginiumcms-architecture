---
title: Iteration 14 – Code Policy and Code Editor Foundation
description: Historical record of code-editing policy and whitelist-based filesystem access
icon: material/history
---

# Iteration 14 – Code Policy and Code Editor Foundation

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Delivered; the original source listed the tag as pending |
| Release / period | historický zdroj uvádza 2.0.3 pending; funkcia je neskôr potvrdená |
| Record type | historical security iteration |

## Goal

Fix Code Editor path resolution, add `CodePolicyEngine`, a security token scanner, Developer Mode unlock, and a path whitelist compatible with the extension architecture.

## Backend

| Area | Implementation |
|---|---|
| Policy | size, syntax, forbidden functions, namespace, and strict-mode rules |
| Scanner | PHP token scan (`T_EVAL`, function calls) |
| Errors | `CodePolicyViolationException` → HTTP `422` with grouped errors |
| Editor | path normalization, project root, backup, typed `FileInfo[]` |
| Whitelist | Modules, Http/Extensions, theme views, config |
| Forbidden | Core, bootstrap, vendor |

## Frontend

`developer.ts`, `DeveloperUnlockGate.tsx`, and `CodeEditor.tsx` provided TOTP/dev-token unlock and policy-error presentation during save.

## Current security interpretation

The scanner is a write-time protection layer, **not a sandbox**. It cannot safely execute untrusted PHP in the same process. Current rules for imports, symlinks, ZIP archives, and AI-generated code are in [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md).

## Continuity

The plugin runtime followed in It.15 and the full-stack editor in It.16.

