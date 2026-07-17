# Iteration 21 – API Contract, Automated Testing & FE Parity

**Status:** Complete  
**Version:** 2.0.9

## Summary

Locked the API response contract across all HTTP controllers, added MSW for frontend-only development, Postman/Newman smoke tests, React Hook Form + Zod on settings, and refreshed API documentation.

## Backend ✅

| Deliverable | Location |
|-------------|----------|
| `JsonResponder` | `success`, `error`, `validation`, `conflict`, `respond`, `paginated` |
| All HTTP controllers | Injected `JsonResponder` (including Backup, Version, AuditTrail) |
| Standard backup list | `GET /api/admin/backups` → `{ success, data: Backup[] }` |
| Health envelope | `GET /api/health` → `{ success, data }` |
| Contract doc | [API_CONTRACT.md](architecture/API_CONTRACT.md) |

## Frontend ✅

| Deliverable | Location |
|-------------|----------|
| MSW handlers | `frontend/src/mocks/` — `VITE_MSW=true` |
| Typed clients | `content.ts`, `user.ts`; fixed `backup.ts`, `audit.ts` |
| RHF + Zod | `SettingsView.tsx` + `validation/zodFromRules.ts` |
| 422 mapping | `validation/mapApiErrors.ts` → `setError()` |

## Tooling ✅

| Artifact | Path |
|----------|------|
| Postman smoke (public) | `docs/api/PaginiumCMS.postman_collection.json` |
| Newman script | `scripts/run-api-smoke.sh` |
| GitHub Actions CI | `.github/workflows/ci.yml` |
| API reference | [architecture/API.md](architecture/API.md) |

## Tests

- `JsonResponderTest`, `ApiResponseShapeTest` (incl. backup envelope)
- `frontend/src/mocks/handlers.test.ts`
- `frontend/src/validation/zodFromRules.test.ts`
- **503+ PHPUnit**, PHPStan L8

## Deferred (post 2.0.9)

- OpenAPI 3.1 YAML export
- Full migration of all components from `useApi` → typed clients ([Iteration 17](ITERATION_17.md))

## Next

→ [Iteration 22](ITERATION_22.md) – ops finish (trash UI, brute-force) + public XML feeds

## Related

- [TESTING.md](developer/TESTING.md)
- [CHANGELOG.md](../CHANGELOG.md) – [2.0.9]
