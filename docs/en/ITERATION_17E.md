---
title: Iteration 17e – API Barrel and CONTRIBUTING
description: Historical record of the Wave 5e MVP for the API↔FE law and CI export checking
icon: material/history
---

# Iteration 17e – API Barrel and CONTRIBUTING

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Shipped |
| Release / period | 2.0.55 |
| Record type | historical Wave 5e increment |

## Goal

Deliver the minimum enforceable foundation of the API↔FE law: a documented contribution workflow, a complete API barrel, and an automated CI check.

## Delivered scope

| Element | File / result |
|---|---|
| Contributing | `docs/developer/CONTRIBUTING.md` |
| API barrel | `frontend/src/api/index.ts` – historically 39 modules and 16 clients |
| Lint | `frontend/scripts/lint-api-barrel.mjs` |
| npm | `npm run lint:api-barrel` |
| CI | dedicated frontend job step |

## Verification

```bash
cd frontend
npm run type-check
npm run lint:api-barrel
npm test -- --run
```

## Outside the MVP

The scaffold wizard, migration of every raw client, and a complete API inventory remained outside this wave. See the main [It.17](ITERATION_17.md).

