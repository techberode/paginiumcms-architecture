---
title: Contributing to PaginiumCMS
description: Workflow for issues, proposals, code, tests, documentation, and safe pull requests
icon: material/source-pull
---

# Contributing to PaginiumCMS

## 0. Current implementation baseline — Wave 5e / It.17

This document remains the general contribution contract. The current branch also enforces the following concrete chain:

```text
route + controller/application service
→ typed frontend API module
→ API barrel export
→ real component/hook/extension consumer
→ API documentation
→ backend + frontend test
```

### Required checklist for every new or changed endpoint

- [ ] The route is registered under `backend/app/Http/Routes/`; plugin routes belong to the controlled extension route tree.
- [ ] The controller uses DI, `JsonResponder`, and the applicable AuthZ, CSRF, 2FA, and Path ACL checks.
- [ ] A typed client exists in `frontend/src/api/{module}.ts`; an extension may use `frontend/src/extensions/{id}/api.ts`.
- [ ] The module is re-exported from `frontend/src/api/index.ts`, and a public `fooApi` object is also exposed as `api.foo` where applicable.
- [ ] A real consumer exists; UI code must not bypass the typed client through ad-hoc `apiClient.get('/api/...')` calls.
- [ ] The endpoint is listed in [API.md](../architecture/API.md), and envelope changes are reflected in [API_CONTRACT.md](../architecture/API_CONTRACT.md).
- [ ] The change has PHPUnit coverage and, for non-trivial frontend logic, Vitest/MSW coverage.
- [ ] `npm run lint:api-barrel` passes.

Explicitly documented server-only CLI, worker, scheduler, or webhook operations may omit a frontend consumer. The exception must not be used as a promise to “wire the frontend later.”

### Current no-merge rules

- external plugins do not belong in `backend/app/Core/`; they use the controlled extension space,
- `.env`, secrets, raw or sanitized test logs, `SECURITY_ISSUES.md`, `PRIVATE_OPS_CHECKLIST.md`, and the local `LOCAL_TEST_LOGS.md` are never committed,
- `LOCAL_TEST_LOGS.md.example` is only the public template for the maintainer workflow,
- changes that break strict types, PHPStan L8, the API barrel, or extension code policy are not merged.

> This document is the entry contract for contributions to the **`v2.1.0-beta.*`** release family. PaginiumCMS is a flat-file platform evolving into a **Hybrid Headless Content Engine**; files remain the mandatory source of truth and derived layers must never replace them.

A contribution does not have to be a large feature. Reproducible bug reports, documentation corrections, regression tests, security hardening rules, and reductions in architectural debt are equally useful.

## 1. Before making the first change

1. Read the project [philosophy](../PHILOSOPHY.md), [architecture](../architecture/ARCHITECTURE.md), and [continuation plan](../CONTINUATION.md).
2. Prepare the local environment through [LOCAL_SETUP.md](LOCAL_SETUP.md).
3. Verify the branch state in the current `CHANGELOG.md`, release notes, and CI. A historical iteration document is not automatically the current backlog.
4. Search [ISSUES.md](../ISSUES.md) and the repository's open issues.
5. For a substantial change, write a proposal first: problem, boundaries, data flow, security impact, migration, and rollback.

Do not begin a large refactor merely because the result might look cleaner. Demonstrate a concrete problem or measurable benefit first. The old reliable rule still works: establish **why** before debating the class name. 🙂

## 2. Contribution types

| Type | Expected output |
|---|---|
| Bug fix | reproduction, regression test, fix, and an `ISSUES.md`/changelog entry when appropriate |
| Feature | contract proposal, backend/FE implementation, tests, documentation, and migration note |
| Refactor | unchanged public behavior or an explicitly documented contract change |
| Documentation | technically verifiable text, valid links, and clear implemented/planned labels |
| Security | private report through the root `SECURITY.md`; public PR only after coordination |
| Extension/theme | manifest, policy scan, compatibility, activation/rollback, and build note |
| Test infrastructure | deterministic tests, isolated storage, and understandable failure output |

## 3. Branches, scope, and commits

Recommended branch names:

```text
feat/content-locales
fix/iss-123-lock-race
docs/hybrid-engine-api
security/plugin-zip-hardening
```

Scope rules:

- one logical change per branch and pull request,
- do not mix mass formatting with a functional fix,
- do not move public APIs or storage paths without a migration plan,
- do not add generated runtime data, local logs, or private operations files,
- update dependencies separately unless they are required by the change.

Recommended commit style:

```text
feat(content): add locale-aware document schema
fix(locks): reject stale revision after heartbeat expiry
test(storage): cover interrupted atomic rename
docs(api): mark JWT endpoints as planned
security(plugins): reject symlink entries during import
```

A maintainer may use the established release-commit convention. A regular contributor must not create a release tag merely because CI is green.

## 4. Non-negotiable architectural invariants

| Invariant | Requirement |
|---|---|
| Flat-file SSOT | SQL, Redis, an index, or a Git remote must not become the only authoritative content source |
| Atomic writes | mutations use the existing repository/storage contract, locking, and safe rename |
| Thin Core | domain behavior belongs in modules; external extensions never belong in `Core/` |
| Backend authorization | a UI guard is not a security check; every protected route verifies role/permission |
| CSRF | session mutations use CSRF middleware; any exemption is narrow and documented |
| Secrets | no secrets in Git, URLs, logs, or client bundles |
| Derived layers | index/cache/Git/translation/AI failure must not silently alter the SSOT |
| Audit | security- or domain-significant mutations produce an auditable result |
| Rollback | migrations, extension imports, and critical settings have a recoverable point |

See [CORE.md](../architecture/CORE.md), [STORAGE.md](../architecture/STORAGE.md), [VERSIONING.md](../architecture/VERSIONING.md), and [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

## 5. The API ↔ frontend ↔ documentation law

For a user-facing or administrative HTTP capability:

```text
Route + middleware
    → controller/application service
    → typed frontend API module
    → real consumer or route
    → API/API_CONTRACT documentation
    → backend + frontend test
```

Checklist for a new or changed route:

- [ ] the route lives in `backend/app/Http/Routes/` or the approved extension route branch,
- [ ] middleware order and permission are explicit,
- [ ] the controller does not contain storage business logic that belongs in a service/repository,
- [ ] the response follows [API_CONTRACT.md](../architecture/API_CONTRACT.md),
- [ ] a typed client exists in `frontend/src/api/` or an isolated extension `api.ts`,
- [ ] the export is added to the API barrel for a standard frontend module,
- [ ] UI code uses the typed client instead of scattered raw requests,
- [ ] [API.md](../architecture/API.md) labels the endpoint as implemented, transitional, or planned,
- [ ] tests cover success, validation, and at least one authorization/error branch.

CLI commands, schedulers, internal workers, webhook receivers, and server-only diagnostics can be explicit exceptions. The exception must be documented; “there is no FE because we forgot it” is not an architecture profile.

## 6. Backend contributions

Minimum rules:

- PHP 8.5+ according to the current project,
- `declare(strict_types=1);` in every new PHP file,
- complete types and PHPStan level 8 with no new errors,
- dependency injection instead of global state and ad-hoc infrastructure construction,
- repository/storage APIs instead of arbitrary direct JSON writes,
- no `include`, shell execution, `unserialize`, or outbound URL outside an approved contract,
- safe handling of paths, filenames, ZIP entries, and user-controlled URLs,
- stable error codes for client-actionable failures.

Before submission:

```bash
composer test
composer stan
composer cs
composer audit
```

When commands differ in a concrete release artifact, `composer.json` and the CI workflow are authoritative, not an old issue screenshot.

## 7. Frontend contributions

- strict TypeScript; a public module must not hide an unknown shape behind `any`,
- functional React components and existing project conventions,
- loading/success/empty/error states where appropriate,
- mutations wait for backend confirmation; optimistic state has rollback,
- `401`, `403`, `409`, `422`, `429`, and non-JSON responses must not collapse into one generic error,
- user-facing admin strings use the i18n layer,
- HTML/Markdown rendering remains sanitized,
- navigation never trusts `returnTo` without validation.

Verification:

```bash
cd frontend
npm ci
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

## 8. Storage, migrations, and fixtures

A file-schema change describes:

1. old and new shape,
2. schema version,
3. transitional reading of the old shape,
4. idempotent migration,
5. backup/rollback,
6. interrupted-run behavior,
7. rebuild of derived index/cache,
8. tests for a real fixture and malformed input.

Tests must never write into a developer's working production storage. Use an isolated temporary tree and remove it after the test.

## 9. Extensions, themes, and Code Editor

External code belongs in approved extension/theme paths and must pass `CodePolicyEngine`. However:

- the policy scanner is a security gate, not a complete sandbox,
- ZIP import rejects traversal, symlinks, and disallowed types,
- activation is a separate action after import,
- a Vite frontend extension is a build-time bundle; source in a ZIP does not automatically become runtime UI without a build,
- Code Editor `Save` does not mean registration, activation, build, Git push, or deployment.

See [PLUGINS.md](../architecture/PLUGINS.md), [THEMES.md](../architecture/THEMES.md), and [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

## 10. Tests and the quality gate

A contribution adds the lowest sensible test layer:

| Change | Required minimum |
|---|---|
| Service/repository | unit test plus a failure branch |
| HTTP route | integration test for status, envelope, and authz |
| Middleware | allow and deny scenarios |
| React logic | Vitest/Testing Library according to risk |
| Storage migration | old fixture, new fixture, repeated run, rollback |
| Security fix | regression test for the original bypass |
| Documentation | valid links and paired SK/EN updates within scope |

Full local gate:

```bash
./scripts/iteration-gate.sh
# or the project's expanded runner
./scripts/run-all-tests.zsh
```

The expanded runner may contain environment-dependent steps. Do not ignore a failure merely because “my part passed”; fix it or explain precisely in the PR why it is unrelated and how the relevant subset was verified.

## 11. Documentation and status claims

Every significant change updates the appropriate document. Use three states:

- **Implemented** — present in code and verifiable,
- **Transitional** — capability exists while its contract is being consolidated,
- **Planned** — proposal or It.68–77 target; it must not be written as a completed feature.

Do not rewrite a historical iteration record as though it had always been different. Current state belongs in the roadmap, changelog, architecture, or a new decision; the historical record may receive a clear supersession note.

## 12. Pull request checklist

- [ ] the problem and goal are explained,
- [ ] scope is narrow and contains no unrelated changes,
- [ ] security and storage impact are evaluated,
- [ ] migration and rollback are described when required,
- [ ] backend and frontend contracts remain aligned,
- [ ] added or updated tests genuinely fail without the fix,
- [ ] the quality gate is green,
- [ ] documentation and changelog/issue records are updated,
- [ ] no secrets, runtime data, or personal data are included,
- [ ] a reviewer can reproduce the manual smoke test from the description.

## 13. What is not merged

- SQL as a new authoritative content store,
- extension code in `backend/app/Core/`,
- a protected mutation without backend authorization or session CSRF,
- direct writes to index/cache instead of the SSOT,
- an endpoint without a contract and client unless explicitly documented as server-only,
- a security fix without a regression test,
- a dependency with an unexplained audit finding,
- a hidden default account, hard-coded token, or demo password,
- a massive generated diff that cannot be reviewed sensibly,
- documentation presenting a plan as implemented behavior.

## 14. Security findings and help

Do not report a vulnerability as a public issue with exploit details. Follow the root `SECURITY.md`. Include version/tag, vector, impact, a harmless reproduction, and suggested mitigation; never send real secrets or production personal data.

For regular support, provide:

- operating system and runtime versions,
- commit/tag,
- deployment profile,
- exact command and exit code,
- redacted log,
- minimal reproduction.

Related documents: [DEVELOPMENT.md](DEVELOPMENT.md), [LOCAL_SETUP.md](LOCAL_SETUP.md), [CODING_STANDARDS.md](CODING_STANDARDS.md), [TESTING.md](TESTING.md), and [BETA_INFRA.md](BETA_INFRA.md).
