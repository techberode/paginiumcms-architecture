---
title: PaginiumCMS Development
description: Daily development workflow, repository map, debugging, and safe platform changes
icon: material/laptop
---

# PaginiumCMS Development

> This document explains **how to work on the project every day**. Environment installation lives in [LOCAL_SETUP.md](LOCAL_SETUP.md), contribution rules in [CONTRIBUTING.md](CONTRIBUTING.md), and detailed style in [CODING_STANDARDS.md](CODING_STANDARDS.md).

## 1. Development mental model

```text
React/Vite admin SPA
        ↓ typed API client
Slim HTTP routes + middleware
        ↓
application/domain services
        ↓
flat-file repositories (SSOT)
        ↓
derived index/cache/audit/jobs
```

Ask four questions for every change:

1. Which layer is authoritative?
2. Where are authorization and input validation enforced?
3. What happens if a write is interrupted or a downstream job fails?
4. How is the change tested and rolled back?

## 2. Repository map

Typical structure:

```text
backend/
  app/Core/                  platform primitives
  app/Modules/               domain modules
  app/Http/                  routes, controllers, middleware, extensions
  bootstrap/                 application assembly
  public/                    the only production PHP docroot
  storage/                   runtime and authoritative flat-file data
  tests/                     PHPUnit tests
frontend/
  src/api/                   typed API modules
  src/components/            React UI
  src/extensions/            build-time frontend extension sources
  src/hooks|context|utils/    shared application layer
scripts/                     first-run, gates, maintenance helpers
docs/                        architecture, user, and developer guides
```

The concrete tag's structure is authoritative. Do not create a directory merely because another framework commonly uses it; it needs clear ownership and dependency direction.

## 3. Recommended daily cycle

```bash
git switch main
git pull --ff-only
git switch -c fix/iss-123-example

# baseline before the change
composer test
composer stan
cd frontend && npm run type-check && npm test -- --run
```

Then:

1. reproduce the bug or write acceptance criteria,
2. add a test that fails for the correct reason,
3. implement the smallest consistent change,
4. run a narrow test during development,
5. run the complete relevant gate,
6. update documentation and issue/changelog records,
7. inspect the diff for accidental runtime files.

Useful commands:

```bash
git status --short
git diff --check
git diff --stat
git diff -- backend/app/Modules frontend/src/api docs/
```

## 4. Backend workflow

### 4.1 Routes and middleware

A route file should declare HTTP mapping and middleware only. Typical flow:

```text
route
→ authentication
→ role/permission
→ CSRF for a session mutation
→ rate limit / domain guard according to risk
→ controller
```

Slim middleware can exhibit LIFO behavior depending on registration. For a security change, do not trust visual order in a file; add an integration test that proves actual runtime order.

### 4.2 Controllers and application services

A controller:

- reads and normalizes HTTP input,
- invokes an application service,
- returns a responder/envelope,
- does not manually implement `flock`, directories, encryption, or domain merge logic.

An application service:

- enforces the domain workflow,
- uses an authorized user context,
- coordinates repositories, audit, and downstream events,
- distinguishes primary SSOT success from a derived-step failure.

### 4.3 Repositories and storage

Use existing abstractions for writes. Safe baseline:

```text
validate → canonicalize path → acquire lock
→ read current revision → write temp file
→ fsync/close as supported → atomic rename
→ release lock → version/audit/index/cache event
```

For multi-file operations, document whether the solution uses a transaction manifest, compensating action, or best-effort workflow. Flat-file does not mean “rule-free”; filesystems are unusually honest teachers.

### 4.4 Exceptions and error codes

- validation: `422`,
- unauthenticated user: `401`,
- insufficient permission or WAF/CSRF: typically `403`,
- revision/lock conflict: `409`,
- rate limit: `429`,
- unexpected failure: safe `5xx` without a production stack trace.

Client-actionable errors use a stable `code` when the concrete contract supports it. Never expose an absolute path, secret, or internal exception detail.

## 5. Frontend workflow

### 5.1 API client

Standard calls belong in typed modules:

```typescript
// src/api/example.ts
export const exampleApi = {
  list: () => apiClient.get<ExampleListResponse>('/api/examples'),
};
```

A component must not scatter URLs, CSRF retries, and envelope parsing throughout the UI. An extension may have an isolated `api.ts`, but it uses the shared secure client.

### 5.2 Server state and UI state

The backend is the SSOT. Distinguish:

- server state: content, users, settings, locks, revisions,
- local UI state: open panel, unsent form draft, filter,
- persisted client preference: only a safe namespaced value, never a token or secret.

An optimistic update must roll back on `409`, `422`, or network failure.

### 5.3 Editor lifecycle

For content editing:

```text
load document + revision
→ acquire/refresh lock
→ local edits / autosave draft
→ explicit save with expected revision
→ resolve 409 conflict
→ release lock
```

Future translation, Git publishing, and AI Apply operations are separate. Save must never perform them silently.

### 5.4 i18n and content locales

Admin UI i18n translates the interface. The It.73 content locale is a separate document model. Do not collapse both into one global `language` switch.

## 6. Running tests during development

Narrow backend test:

```bash
./vendor/bin/phpunit --filter ExampleServiceTest
./vendor/bin/phpstan analyse --level=8 backend/app/Modules/Example
```

Narrow frontend test:

```bash
cd frontend
npm test -- --run src/api/example.test.ts
npm run type-check
```

Before a PR:

```bash
composer gate
# or
./scripts/iteration-gate.sh
```

The expanded runner may include build, audit, smoke collection, diagnose, and other steps. Read the current script for the exact list.

## 7. Local data and test isolation

- Do not commit `backend/storage` runtime content unless it is an intentional fixture.
- Never use a production user export as test data.
- Keep fixtures minimal and remove personal data.
- Each test gets its own temporary root or unique namespace.
- Parallel tests must not share a lock/index file without coordination.
- `APP_ENV=testing` and explicit test overrides must prevent a local `DEMO_MODE=true` or production secrets from leaking into tests.

When a local index is broken, diagnose authoritative storage before rebuilding a derived layer:

```bash
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --fix
```

## 8. Debugging

### 8.1 HTTP errors

Record:

- method + path,
- status and `content-type`,
- request/correlation ID when available,
- role/permission without exposing the session,
- CSRF state for a mutation,
- server log and audit event.

The frontend must not assume JSON for every `403`; a WAF or reverse proxy can return text, HTML, or an empty body.

### 8.2 Authentication/session problems

Check cookie flags, origin, HTTPS, proxy headers, `TRUSTED_PROXIES`, system time, and whether session storage persists across requests. `SESSION_STRICT=false` can be a local proxy compatibility setting, not a universal production recommendation.

### 8.3 Storage problems

Inspect:

```bash
find backend/storage -maxdepth 3 -type d -printf '%m %u:%g %p\n'
df -h
df -i
```

Do not use `chmod -R 777`. Correct ownership and the smallest required mode for the PHP-FPM/container user.

### 8.4 Frontend build problems

```bash
cd frontend
rm -rf node_modules
npm ci
npm run type-check
npm run build
```

A frontend extension source change requires a rebuild. PHP plugin activation does not create a new Vite bundle.

## 9. Jobs, Git, cache, and external providers

These are downstream layers:

- a local SSOT write has its own result,
- a job carries a stable payload, identity/permission snapshot, and idempotency rule,
- retry must not create duplicate commits, translations, or notifications,
- outbound URLs pass the SSRF guard,
- secrets are loaded server-side and not merely visually redacted in the FE,
- Redis/Git/provider failures are visible and recoverable.

Implement It.68–77 capabilities only through their approved contracts.

## 10. Developing extensions and themes

Backend extension:

```text
backend/app/Http/Extensions/{plugin-id}/
backend/app/Http/Routes/extensions/{plugin-id}.php
```

Frontend extension:

```text
frontend/src/extensions/{plugin-id}/
```

Every import and write of untrusted extension code passes the policy gate. Activation, frontend build, and deployment are separate. Higher-risk execution requires process/container isolation; `CodePolicyEngine` is not a VM.

## 11. Documentation workflow

For a contract change, update at least:

| Change | Document |
|---|---|
| endpoint/response | `architecture/API.md`, `API_CONTRACT.md` |
| storage/schema | `architecture/STORAGE.md`, `VERSIONING.md` |
| new module/event | `architecture/MODULES.md`, `EVENTS.md` |
| extension rule | `PLUGINS.md`, `EXTENSION_CODE_POLICY.md` |
| user workflow | the relevant `user/*.md` |
| release/incident | `CHANGELOG.md`, `ISSUES.md`, release notes |

Keep SK and EN branches semantically equivalent. The translation need not be literal, but it must not alter status, security boundaries, or acceptance criteria.

## 12. Definition of Done

A change is done when:

- implementation follows architecture and security invariants,
- tests cover success and the relevant failure branch,
- local gate and CI are green,
- migration/rollback are verified when data changes,
- API and frontend remain aligned,
- documentation is truthful about status,
- the diff contains no secrets or runtime debris,
- a reviewer can reproduce the result.

Related: [CONTRIBUTING.md](CONTRIBUTING.md), [LOCAL_SETUP.md](LOCAL_SETUP.md), [CODING_STANDARDS.md](CODING_STANDARDS.md), [TESTING.md](TESTING.md), [BETA_INFRA.md](BETA_INFRA.md).
