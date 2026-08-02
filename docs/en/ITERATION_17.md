---
title: Iteration 17 – API-to-Frontend Scaffold Law
description: Historical record of the endpoint–typed client–consumer–documentation rule and migration
icon: material/history
---

# Iteration 17 – API-to-Frontend Scaffold Law

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | 🟡 Partially complete; Wave 5e MVP shipped |
| Release / period | 2.0.9 + 2.0.55 Wave 5e |
| Record type | historical process iteration |

## The law

```text
endpoint + middleware
→ controller/application service
→ typed frontend API module
→ real consumer
→ API documentation
→ backend + frontend test
```

A server-only CLI/worker/scheduler may be an exception, but it must be documented explicitly.

## Delivered scope

Typed modules accumulated over time; 2.0.9 fixed `content.ts`, `user.ts`, and the barrel. Wave 5e delivered complete `api/index.ts` exports, `CONTRIBUTING.md`, `lint-api-barrel`, and a CI step.

## Remaining debt

The source lists raw `useApi`/`apiClient.get` consumers, the “New extension” scaffold wizard, and a complete endpoint inventory as unfinished. The later It.05 documentation substantially refreshed the inventory, but the source does not confirm migration of every component.

## Chronology note

The original text called this law a prerequisite for It.15 even though the plugin runtime subsequently shipped. Current interpretation: new or changed extension endpoints must follow the law, while existing legacy debt is migrated incrementally.

## Related

MVP detail: [ITERATION_17E.md](ITERATION_17E.md). Contributing workflow: [CONTRIBUTING.md](developer/CONTRIBUTING.md).

