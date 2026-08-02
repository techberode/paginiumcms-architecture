---
title: Coding Standards
description: Binding PHP, TypeScript, architecture, security, and documentation rules for PaginiumCMS
icon: material/code-braces
---

# PaginiumCMS Coding Standards

> **Canonical document.** Applies to Core, modules, HTTP, frontend, extensions, themes, and code processed by Code Editor. `CodePolicyEngine` is an implemented protection layer; it is neither merely a future plan nor a complete sandbox.

Detailed rules for untrusted extension code live in [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md). The stricter security rule wins when documents overlap.

## 1. Rule layers

| Layer | Enforcement point | Examples |
|---|---|---|
| Architectural invariant | design, review, tests | flat-file SSOT, extensions outside Core |
| Static quality | CI/local gate | PHPStan L8, TypeScript, ESLint |
| Write/import policy | backend runtime | path, syntax, security scan, manifest, size |
| Runtime security | middleware/services | RBAC, CSRF, SSRF guard, rate limit |
| Operational policy | deployment/ops | HTTPS, docroot, secrets, backup |

No single layer replaces the others. Code that passes a scanner can still contain a business-logic vulnerability; a green unit test does not prove correct proxy configuration.

## 2. Architectural laws

### 2.1 Flat-file SSOT

Authoritative state lives in approved file stores. It is forbidden to:

- introduce SQL as a mandatory content source,
- write only to Redis/an index and update files “later”,
- treat a Git remote as the only copy,
- store a secret only in frontend storage.

Indexes, caches, Git mirrors, search, translation, and AI artifacts are derived or downstream layers.

### 2.2 Thin Core

`backend/app/Core/` contains stable platform primitives only. Domain capabilities belong in `Modules/`; HTTP adapters, middleware, and external extensions belong in `Http/`.

External plugin:

```text
backend/app/Http/Extensions/{plugin-id}/
backend/app/Http/Routes/extensions/{plugin-id}.php
frontend/src/extensions/{plugin-id}/
```

Plugin code never belongs in `Core/`, `bootstrap/`, or `vendor/`.

### 2.3 API ↔ frontend contract

A user-facing endpoint has a typed client, consumer, documentation, and tests. An explicit server-only endpoint is allowed but must be labelled.

### 2.4 Policy before write/import

Untrusted or administrator-edited code passes:

```text
PATH → SIZE/TYPE → SYNTAX → SECURITY → COMPATIBILITY/MANIFEST
→ backup → atomic write/import → audit
```

On failure, the target file or package is not activated. The HTTP response uses `422` for content/policy failure and `403` for a forbidden path/action according to the concrete contract.

## 3. PHP backend

### 3.1 Baseline

| Rule | Requirement |
|---|---|
| Runtime | PHP 8.5+ according to the current project |
| Strict types | `declare(strict_types=1);` in every new file |
| Types | typed parameters, returns, and properties; minimize mixed |
| Analysis | PHPStan level 8 without a baseline hiding new errors |
| Style | readable PSR-12-compatible formatting following the repository |
| DI | constructor injection or approved factory/config registration |
| I/O | repository/driver abstractions, not arbitrary global helpers |
| Time | UTC in persisted data; timezone at presentation |
| Identifiers | stable IDs/slugs are validated and canonicalized |

Required header:

```php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Example;
```

### 3.2 Classes and responsibilities

- controller handles HTTP adaptation,
- application service coordinates a use case,
- domain service owns a domain rule,
- repository owns persistence and concurrency,
- driver/provider isolates an external system,
- DTO/value object carries a validated shape,
- middleware owns cross-cutting HTTP policy.

A god service combining filesystem, HTTP client, authorization, HTML sanitization, and mail does not get one more responsibility “for now”.

### 3.3 Errors and exceptions

- use domain-named exceptions or the module's established result type,
- do not catch `Throwable` merely to return `success: false`,
- let the central error handler process unexpected failures,
- production responses contain no stack trace, absolute path, or secret,
- logs carry correlation, not tokens/cookies.

### 3.4 Filesystem

- construct paths from canonical segments, not direct user input,
- use `realpath`/root-boundary verification for existing files,
- reject `..`, NUL, absolute paths, and symlink escape,
- write through a temp file plus safe rename,
- coordinate through the existing lock/OCC contract,
- use predictable JSON encoding flags and check errors,
- never assume rename or chmod behaves identically across volumes without tests.

### 3.5 Safe outbound calls

- validate URLs through the approved guard,
- prefer HTTPS in production,
- block loopback/private/link-local/metadata ranges according to policy,
- revalidate redirects,
- require timeout, size limit, and redirect limit,
- never place secrets in query strings or logs,
- treat provider responses as untrusted input.

## 4. TypeScript and React

### 4.1 Types

- keep `strict` enabled,
- ban `any` in exported APIs; use `unknown` plus validation,
- response types must not claim a required field the backend does not guarantee,
- prefer discriminated unions for stateful workflows,
- enum/string unions match the backend schema.

### 4.2 API modules

```typescript
export type Example = {
  id: string;
  revision: string;
};

export const exampleApi = {
  get: (id: string) => apiClient.get<Example>(`/api/examples/${encodeURIComponent(id)}`),
};
```

Rules:

- encode path segments,
- build queries through `URLSearchParams` or a shared helper,
- do not store session tokens in `localStorage`,
- mutations use the shared CSRF flow,
- export endpoint modules through the project barrel,
- UI avoids raw requests when a typed module exists.

### 4.3 React components

- a component has one understandable responsibility,
- side effects belong in `useEffect` only for synchronization with an external system,
- do not “fix” dependency arrays by disabling lint without a reason,
- async work handles unmount/cancellation or ignores stale responses,
- forms display field errors from `422`,
- `409` conflict gets dedicated UX rather than a generic toast,
- non-JSON `403` is handled safely.

### 4.4 Accessibility and i18n

- use `button`/`a` for interaction, not a clickable `div`,
- labels, focus order, and keyboard workflow are part of Definition of Done,
- color is not the only status carrier,
- UI text goes through an i18n key,
- format dates/numbers by locale while persisting a stable form,
- content locale and UI locale remain separate.

### 4.5 Safe rendering

- no `dangerouslySetInnerHTML` without central sanitization,
- allow-list URL schemes,
- external links use safe `rel` according to policy,
- SVG/upload is not automatically trusted,
- Markdown rendering does not allow raw HTML without explicit sanitization.

## 5. Naming and structure

| Element | Convention | Example |
|---|---|---|
| PHP class/interface | PascalCase | `ContentRepository` |
| PHP method/property | camelCase | `findPublished()` |
| TS type/component | PascalCase | `ContentEditor` |
| TS function/variable | camelCase | `loadContent()` |
| Constant | existing project convention, usually UPPER_SNAKE_CASE | `MAX_UPLOAD_BYTES` |
| Route path | lowercase kebab/segment following existing API | `/api/admin/content-meta` |
| Plugin ID | `a-z0-9-`, kebab-case | `weather-widget` |
| JSON key | stable existing schema, usually camelCase | `updatedAt` |
| Test | behavior, not implementation detail | `rejects stale revision` |

Use acronyms consistently: `ApiClient` or the project's established form, not a mix of `APIClient`, `ApiCLIENT`, and `api_client`.

## 6. CodePolicyEngine and the write-time gate

### 6.1 What the engine does

Depending on context it can validate:

- root/path allow-list and traversal,
- file type and size,
- PHP/JSON/YAML syntax,
- forbidden PHP tokens/functions,
- extension namespace and manifest,
- specific untrusted artifacts such as shortcode/layout definitions.

### 6.2 What the engine does not do

- prove the absence of business-logic vulnerabilities,
- isolate infinite loops, memory bombs, or side channels,
- guarantee a safe third-party JS bundle,
- replace OS/container sandboxing,
- replace dependency review and capability modeling.

### 6.3 Typical forbidden extension constructs

```text
eval, shell_exec, exec, system, passthru, proc_open,
dynamic include/require outside the extension root,
unsafe unserialize,
direct reading of .env or data/users,
direct outbound clients bypassing the guard,
symlink/absolute path/traversal in a package
```

The concrete implementation and settings are authoritative. An exception must never be a silent wildcard; it needs an owner, reason, minimum scope, audit, and expiry/review.

## 7. Extensions and themes

A manifest identifies at least ID, name, version, and compatibility. Import workflow:

```text
upload to temporary location
→ archive limits + Zip-Slip/symlink checks
→ manifest/schema
→ scan every allowed file
→ stage files
→ registry record disabled
→ explicit enable
```

Frontend sources in an extension package are build-time. Enabling a backend plugin must not claim that arbitrary new React UI has been loaded dynamically.

A theme/color scheme and executable plugin are not the same trust level. Prefer declarative tokens and schemas over executable code.

## 8. Testing standards

### 8.1 Backend

- Arrange/Act/Assert or an equally readable form,
- every test isolates its filesystem root,
- do not compare unstable timestamps without controlling the clock,
- concurrency tests use a deterministic trigger/barrier, not random `sleep`,
- security tests use harmless payloads,
- HTTP tests verify status, body contract, and side effect.

### 8.2 Frontend

- test user-observable behavior,
- mock transport at the API-module boundary rather than every hook internals,
- cover loading/error/empty and conflict branches according to risk,
- snapshots do not replace meaningful assertions,
- do not increase timeouts as the first flaky-test fix.

### 8.3 Regressions

Every bug fix has a test that:

1. fails without the fix,
2. passes with the fix,
3. names the original vector,
4. contains no real secret or harmful production action.

## 9. Documentation and comments

A comment explains **why**, an invariant, or a non-obvious boundary. It does not mechanically narrate the next line.

Document public contracts where another developer will find them:

- API in architecture docs,
- storage schema in storage/versioning,
- extension capability in plugin policy,
- operations requirements in deploy docs,
- incidents in `ISSUES.md` and the changelog.

Do not mix `implemented`, `transitional`, and `planned` states.

## 10. Security checklist before merge

- [ ] backend permission on every protected route,
- [ ] CSRF on a session mutation or a documented narrow exemption,
- [ ] path/URL/file input canonicalized,
- [ ] upload/archive has limits and type validation,
- [ ] output/rendering is contextually escaped or sanitized,
- [ ] secrets are not displayed, logged, or placed in URLs,
- [ ] audit/log data resist CR/LF/CSV injection,
- [ ] rate limiting and abuse model considered,
- [ ] retry/idempotency for jobs or external providers,
- [ ] rollback and backup for writes/migrations,
- [ ] regression test for a security fix.

## 11. Quality-gate commands

```bash
composer test
composer stan
composer cs
composer audit

cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

Whole project:

```bash
./scripts/iteration-gate.sh
```

The current CI workflow and package scripts are authoritative. A test count is not a coding standard; zero relevant failures and meaningful coverage are.

## 12. Exceptions and changing the standard

An exception records:

- exact rule,
- reason,
- affected files/capability,
- risk and mitigation,
- owner,
- review date or condition.

A material change to this document requires architecture review and SK/EN synchronization. Silently disabling a lint/scanner rule in one module is not a standards change; it is a bypass.

Related: [CONTRIBUTING.md](CONTRIBUTING.md), [DEVELOPMENT.md](DEVELOPMENT.md), [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md), [SECURITY.md](SECURITY.md), [API_CONTRACT.md](../architecture/API_CONTRACT.md).
