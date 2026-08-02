---
title: Iteration 21 – API Contract, Automated Testing and Frontend Parity
description: Historical record of the JsonResponder contract, MSW, smoke tests, and schema-driven validation
icon: material/history
---

# Iteration 21 – API Contract, Automated Testing and Frontend Parity

> **Historical delivery record.** This document describes the iteration as captured in the 2 August 2026 source archive. Later security fixes and status changes are identified separately. Current rules in `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md`, and the release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete |
| Release / period | 2.0.9 |
| Record type | historical contract and testing iteration |

## Goal

Standardize JSON responses across HTTP controllers, improve frontend-only development with MSW, add Postman/Newman smoke tests, and map backend validation into React Hook Form and Zod.

## Delivered contract

| Area | Delivery |
|---|---|
| Backend | `JsonResponder` for success, error, validation, conflict, pagination, and generic responses |
| Frontend | typed clients, MSW handlers, RHF + Zod in settings, and `422` mapping through `setError()` |
| Tooling | Postman collection, Newman script, GitHub Actions CI, and refreshed API documentation |
| Tests | response-shape tests, MSW handler tests, and `zodFromRules` tests |

## Current interpretation

The historical goal that “all responses share one JSON envelope” now applies to the application API layer. A WAF or reverse proxy may return plain text or an empty body before routing; the frontend must inspect status and `Content-Type` before JSON parsing. The legacy authentication envelope is another documented exception.

The canonical contract is [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Deferred scope

OpenAPI 3.1 export and full migration from generic `useApi` calls to typed clients remained deferred in the source. The Postman collection is a smoke subset, not a complete specification.

## Historical tests

The source reported more than 503 PHPUnit tests and PHPStan L8. This is a 2.0.9 snapshot, not the current count. The current release gate is defined in [TESTING.md](developer/TESTING.md) and [RELEASE.md](developer/RELEASE.md).

