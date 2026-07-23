# Contributing to PaginiumCMS

> **Wave 5e / Iteration 17 MVP** — API↔FE law, barrel exports, CI lint.  
> Related: [CODING_STANDARDS.md](CODING_STANDARDS.md) · [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md) · [API.md](../architecture/API.md)

---

## Before you start

1. Read [PHILOSOPHY.md](../PHILOSOPHY.md) and [CONTINUATION.md](../CONTINUATION.md) (architectural laws).
2. Run the quality gate locally before pushing:

```bash
composer test && composer stan
cd frontend && npm run type-check && npm run lint && npm run lint:api-barrel && npm test
```

3. One wave = one release tag = green CI (see [RELEASE.md](RELEASE.md)).

---

## Architectural laws (non‑negotiable)

| Law | Rule |
|-----|------|
| **Extensions** | External plugins live only under `Http/Extensions/{id}/`, never in `Core/` |
| **API↔FE** | Every new backend endpoint needs a typed FE client + consumer + docs row |
| **Code policy** | Custom code passes `CodePolicyEngine` before write (422 on failure) |

Detail: [CODING_STANDARDS.md §2](CODING_STANDARDS.md#2-architektonické-zákony).

---

## ZÁKON API↔FE (Iteration 17)

```
Route + Controller  →  frontend/src/api/{module}.ts  →  component/hook  →  docs/architecture/API.md
```

### New admin or public endpoint checklist

Use this for **every** new or changed REST route:

- [ ] **Route** registered under `backend/app/Http/Routes/` (or `Routes/extensions/{id}.php` for plugins)
- [ ] **Controller** uses DI, `JsonResponder`, AuthZ/CSRF where required ([security baseline](../../.cursorrules))
- [ ] **Typed client** in `frontend/src/api/{module}.ts` (prefer `export const {module}Api = { ... }`)
- [ ] **Barrel** — add `export * from './{module}'` and register on `export const api` in [`frontend/src/api/index.ts`](../../frontend/src/api/index.ts)
- [ ] **Consumer** — React component, hook, or extension entry uses the typed client (not raw `apiClient.get('/api/...')` in UI code)
- [ ] **Docs** — row in [`docs/architecture/API.md`](../architecture/API.md) (method, path, FE file)
- [ ] **Tests** — PHPUnit for backend; Vitest for non-trivial FE client logic
- [ ] **CI** — `npm run lint:api-barrel` passes

### Extension endpoints

Plugin routes under `/api/extensions/{id}/…` must also have:

- `frontend/src/extensions/{id}/api.ts` (or functions in `index.ts`)
- Entry documented in [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md)

Reference: bundled **`hello-widget`** (Wave 5d).

---

## API barrel (`frontend/src/api/index.ts`)

The barrel is the **single import surface** for typed API modules:

```typescript
import { api, contentApi, queryKeys } from '../api';
// Prefer: api.content.listPages() or direct contentApi.*
```

Rules:

1. Every `src/api/*.ts` file (except `client`, `types`, `queryKeys`, tests) must be re-exported from `index.ts`.
2. Every `export const fooApi = { … }` must appear as `api.foo` on the `export const api` object.
3. Run `npm run lint:api-barrel` — enforced in CI (Wave 5e).

Internal-only helpers may stay as named function exports (`export async function listMedia()`); they still need `export * from './media'`.

---

## PHP backend

- `declare(strict_types=1);` in every new file
- PHPStan level **8**, zero errors
- Register services in `backend/app/Http/Config/services.php` or module `Config/services.php`
- Flat-file writes use `flock(LOCK_EX)` via existing repositories

See [CODING_STANDARDS.md §1](CODING_STANDARDS.md#11-php-backend).

---

## Frontend

- Strict TypeScript, functional React components
- Async UI: Loading / Success / Error states
- Backend is SSOT — never assume state the API did not confirm
- i18n: admin strings via `useI18n()` / `t('…')` (see [ITERATION_18.md](../ITERATION_18.md))

---

## Commits and releases

- Follow existing commit style: `release: 2.0.xx — …` or scoped `fix:`, `docs:`, `feat:`
- Do not skip CI hooks
- Release checklist: [RELEASE.md](RELEASE.md)
- Document incidents in [ISSUES.md](../ISSUES.md) when fixing regressions

---

## What we do not merge

- Endpoints without a typed FE client (unless explicitly documented as server-only/cron)
- Extensions in `backend/app/Core/`
- Secrets, `.env`, or private ops files (`SECURITY_ISSUES.md`, `PRIVATE_OPS_CHECKLIST.md`)
- PHP without strict types or failing PHPStan
- Broken `npm run lint:api-barrel`

---

## Getting help

- Architecture map: [PLUGINS.md](../architecture/PLUGINS.md), [API_CONTRACT.md](../architecture/API_CONTRACT.md)
- Iteration detail: `docs/ITERATION_{N}.md`
- Continuation plan: [CONTINUATION.md](../CONTINUATION.md)
