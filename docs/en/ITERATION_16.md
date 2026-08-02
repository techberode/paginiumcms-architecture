---
title: Iteration 16 – Full-Stack Code Editor
description: Historical record of Monaco, file tree, create/delete, backup restore, and safety UX
icon: material/history
---

# Iteration 16 – Full-Stack Code Editor

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Core stack complete; extension bundle editor is not confirmed |
| Release / period | 2.0.22 + neskorší It.15 runtime |
| Record type | historical developer-tool iteration |

## Goal

Deliver a Monaco-based editor over an explicit whitelist, Developer Mode unlock, hierarchical file tree, safe save, create/delete, and backup restore.

## Delivered scope

| Element | Status |
|---|---|
| Monaco | formatting, word wrap, theme synchronization |
| Gate | TOTP/dev-token unlock plus explicit lock |
| Filesystem | all allowed roots and hierarchical tree |
| Save | policy, syntax check, and pre-save backup |
| Create/Delete | separate HTTP actions and confirmations |
| Restore | backup list and restore |
| UX | warning banner and save/delete/lock confirmations |

## Whitelist

Modules, Http/Extensions, theme views, and config were allowed. Core, bootstrap, and vendor remain outside the editor.

## Unclosed scope

The original document marked the plugin-bundle editor as blocked by It.15. It.15 has shipped, but the source does not confirm that a separate package editor was subsequently implemented. It therefore remains **unconfirmed**, not automatically complete.

## Security contract

Saving a file is not plugin registration/activation and is not a frontend build. Current rules: [CODE_EDITOR.md](user/CODE_EDITOR.md), [DEVELOPER_MODE.md](user/DEVELOPER_MODE.md), [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md).

