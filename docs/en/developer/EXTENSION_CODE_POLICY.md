---
title: Extension Code Policy
description: Mandatory fail-closed policy for plugins, future themes, and untrusted authoring
icon: material/shield-code-outline
---

# PaginiumCMS Extension Code Policy

> **Status:** mandatory architecture and security contract for code outside Core.  
> **Applies to:** plugin ZIP import, extension source in Code Editor, reference plugins, future theme packages, shortcode definitions, and other untrusted authoring paths.

This policy complements coding standards, plugin architecture, Core hardening, and the API contract. Automated checks reduce risk, but a PHP extension is still not a sandbox. A scanner-approved package means only “no known forbidden pattern was detected and the declared contract is satisfied,” not “provably safe.”

---

## 1. Core principles

1. **Core is closed to external writes.**
2. **Untrusted code is validated fail-closed before both persistence and activation.**
3. **Import and enable are separate events.**
4. **Every capability is explicit in manifest and allow-list.**
5. **Server authorization and policy never rely on frontend controls.**
6. **No weaker alternate write path through Monaco, ZIP, scaffold, or API.**
7. **A plugin is not implicitly trusted merely because SUPER_ADMIN uploaded it.**
8. **Code, data, configuration, secrets, and derived artifacts have different rules.**
9. **Safe rejection is better than partial installation.**
10. **A documented capability is public contract; an internal class is not extension API.**

---

## 2. Trust realms

| Realm | Example | Trust | Policy |
|-------|---------|-------|--------|
| platform Core | `backend/app/Core/` | highest, shipped/reviewed | project CI; external writes forbidden |
| internal module | `backend/app/Modules/` | shipped/reviewed | full CI + architecture rules |
| external plugin | `backend/app/Http/Extensions/{id}/` | untrusted | forced untrusted policy |
| future theme | `backend/resources/views/themes/{id}/` | presentation-oriented, still untrusted | theme profile + asset/template policy |
| shortcode/layout definition | `data/shortcodes`, `data/layout`, or virtual buffer | untrusted data | schema + safe expansion policy |
| generated cache/build | cache/index/compiled HTML | derived | not source of truth; rebuildable |

A directory name is not the only trust signal. Imported files are validated as untrusted in a temporary directory or under a virtual `untrusted://…` path.

---

## 3. Placement

| Type | Backend | Routes | Frontend | Registry |
|------|---------|--------|----------|----------|
| Plugin | `backend/app/Http/Extensions/{id}/` | `backend/app/Http/Routes/extensions/{id}.php` | `frontend/src/extensions/{id}/` | `data/plugins.json` |
| Theme package | `backend/resources/views/themes/{id}/` | only when explicitly supported | `frontend/src/themes/{id}/` | planned `data/themes.json` |
| Internal module | `backend/app/Modules/{Name}/` | central/registered HTTP layer | feature-owned FE | shipped config/catalog |

Forbidden to an external package:

```text
backend/app/Core/
backend/bootstrap/
backend/vendor/
.env
server/system paths
arbitrary data/ writes
public web root outside approved assets
```

Canonical path is checked after normalization and symlink resolution. A raw string prefix check is insufficient.

---

## 4. Allowed files and limits

A policy profile defines allowed extensions and maximum size. A typical plugin may contain:

- `.php`, `.json`, `.md`, `.txt`,
- supported `.ts`, `.tsx`, `.css` only as build-managed frontend source,
- approved image/font-reference assets according to media policy,
- test fixtures without secrets.

Forbidden or requiring special review:

- executable binary, shared object, PHAR,
- nested archive,
- symlink/hardlink,
- `.env`, private key, certificate private material,
- vendor tree without an approved dependency model,
- obfuscated/base64-packed executable payload,
- oversized file,
- MIME/extension mismatch.

Fonts are not included in documentation packages or distributed as “part of a theme” without clear licensing and deployment policy.

---

## 5. Manifest policy

Minimum plugin fields:

| Field | Rule |
|-------|------|
| `id` | kebab-case `[a-z0-9]+(-[a-z0-9]+)*`; matches directory |
| `name` | readable name with length limit |
| `version` | SemVer |
| `minCmsVersion` | supported SemVer constraint/value |
| `hooks` | map containing only `HookCatalog` values |
| `routes` | boolean/capability declaration |
| `frontend` | boolean or future versioned FE descriptor |

Manifest:

- uses UTF-8 JSON without duplicate keys,
- contains no secrets or host paths,
- cannot point to arbitrary PHP classes outside plugin namespace,
- cannot declare arbitrary remote script URLs,
- must declare `manifestVersion` when multiple schemas exist,
- is rejected on unknown required capability,
- ignores future optional fields only under an explicitly safe compatibility rule.

ID is stable identity. Display name may change; ID/path/namespace do not change during upgrade without an explicit migration.

---

## 6. PHP rules

Every extension PHP file:

```php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\ExamplePlugin;
```

Mandatory:

- `strict_types=1`,
- namespace matches extension ID and allowed prefix,
- PSR-compatible autoload/class naming according to the supported loader,
- typed parameters and returns in public extension API,
- no include-time side effects beyond declared registration,
- no global function/class names colliding with another plugin,
- no hidden custom DI/service locator that bypasses capability contracts.

### Forbidden constructs

The minimum forced-untrusted list includes:

```text
eval
exec
shell_exec
system
passthru
proc_open
popen
pcntl_*
assert(string)
create_function
include/require using dynamic or non-allow-listed paths
unserialize of untrusted data
call_user_func* over uncontrolled input
FFI
```

Scanner should use tokens/AST rather than regex alone. Aliases, namespaced functions, concatenation, or escaping must not bypass a simplistic check. The list may grow; policy is deny-first, not a promise that everything unlisted is automatically allowed.

---

## 7. Filesystem and data

A plugin has no generic filesystem capability. It uses a namespaced storage/repository contract:

```text
extensions/{id}/settings
extensions/{id}/content/{documentId}
extensions/{id}/state/{key}
```

Forbidden:

- reading `.env`, session files, another module's backups, or server secrets,
- writing Core, bootstrap, vendor, or another plugin,
- using a user path without canonical resolution,
- following a symlink outside the allowed root,
- storing plaintext credentials,
- treating cache as authoritative SSOT.

Authoritative plugin data is included in backup/export, or the manifest explicitly declares state as derived and rebuildable.

---

## 8. Hook policy

A plugin registers hooks only through its manifest. Allowed name and payload are defined by `HookCatalog`.

Handler:

```php
public static function onContentAfterSave(array $context): void
```

Rules:

- unknown hook → import/enable failure,
- class must belong to plugin namespace,
- payload is treated as read-only,
- handler must not depend on undocumented keys,
- sensitive fields are excluded from public hook payload,
- exception behavior is defined per hook,
- expensive side effects move to a queue/job with explicit identity and retry policy,
- handler must not autonomously publish content outside permission/policy flow.

A `before_*` hook cannot bypass server validation. An `after_*` hook must not roll back an already committed SSOT write through an arbitrary exception without a transaction contract.

---

## 9. Route policy

A plugin route must:

- be a declared capability,
- use the supported registrar signature,
- have explicit public/admin classification,
- have route-level permission/scopes,
- use CSRF for cookie-authorized mutation,
- validate body/query/path,
- use rate and size limits according to threat model,
- use the central response/error contract,
- audit sensitive mutations,
- avoid wildcard CORS and open redirects,
- avoid filesystem paths/stack traces in production responses.

A public route is not public merely because middleware was omitted. Public classification is intentional and tested.

An endpoint has a corresponding typed frontend client or is explicitly headless. Scattered raw `fetch` in components violates the API↔FE law.

---

## 10. Outbound network and providers

Direct `curl`/socket access to arbitrary URLs is not a default capability. Outbound goes through a platform client/provider contract with:

- `https` allow-list,
- DNS/IP checks against localhost, RFC1918, link-local, and metadata endpoints,
- redirect revalidation,
- timeout, response size limit, and content-type validation,
- authorization-header redaction in logs,
- per-plugin/provider rate limit,
- secrets stored in encrypted settings,
- explicit user/admin configuration.

A plugin must not use backend as an SSRF proxy or secretly exfiltrate content/analytics to a third party.

---

## 11. Frontend policy

Frontend extension source:

- uses strict TypeScript,
- exports through a documented entry contract,
- uses the central API client,
- respects route/slot/menu registration,
- uses semantic theme tokens,
- namespaces CSS,
- sanitizes HTML and URLs,
- does not store bearer/admin secrets in `localStorage`,
- does not use `eval`, `new Function`, inline script injection, or unverified remote bundles,
- does not patch Core source or global prototypes.

### Build-time boundary

`import.meta.glob` includes files at build time. Importing a ZIP with `frontend/` must therefore:

- either mark plugin as backend-only until the next build,
- or run a supported isolated and audited build/redeploy pipeline,
- or in the future use a signed prebuilt bundle with integrity/CSP/ABI contract.

Dynamic `eval` or raw remote `<script>` is not an acceptable workaround.

---

## 12. Theme policy

A future theme has a narrower capability profile than a plugin:

- declarative templates/slots/tokens/assets,
- no business logic or direct repository writes,
- no arbitrary PHP in the untrusted profile,
- no script URLs outside an approved bundle model,
- asset MIME/size/license validation,
- preview without SSOT mutation,
- fallback for missing slots/assets,
- accessibility and contrast tests.

If a theme package contains executable PHP, it is security-classified as a plugin or trusted shipped theme, not passive design.

---

## 13. Shortcode, layout, and AI-generated artifacts

A shortcode/layout definition is **data**, not PHP:

- JSON schema,
- allow-listed attributes and `pg-*` classes,
- no scripts/iframes/event handlers,
- expansion template is sanitized,
- preview uses the same validator as save,
- unknown shortcode fails safely,
- artifact is not activated after `422`.

AI proposals, translations, and scaffolds have no policy exemption. AI may produce proposal/diff, but Apply is performed by an authorized user through the same validation pipeline. AI never receives generic shell or extension-superuser authority.

---

## 14. Import, activation, and rollback

```text
ZIP/Monaco/scaffold input
→ temporary/virtual untrusted buffer
→ path + type + size
→ syntax
→ security scan
→ manifest/artifact schema
→ compatibility
→ stage files
→ registry enabled=false
→ explicit enable
→ smoke test
```

Failure rules:

- validation failure activates nothing,
- partial files are removed or remain in isolated quarantine temp space,
- registry is not overwritten with an inconsistent state,
- enable failure rolls back runtime registration,
- previous plugin remains recoverable after failed upgrade,
- error report is machine-readable and redacted,
- every stage emits an audit event.

---

## 15. Compatibility and versions

A plugin declares minimum CMS version and its own SemVer. A recommended future contract adds:

- `manifestVersion`,
- supported hook/API ABI versions,
- required capabilities,
- optional capabilities,
- checksum/signature metadata,
- migration version.

`minCmsVersion` alone does not guarantee compatibility with every future major version. A breaking extension API change requires deprecation, migration guide, and contract-test fixtures.

---

## 16. Secrets and configuration

A plugin may declare a settings schema, not its own plaintext secret store.

- secret field is write-only/redacted,
- stored through platform EncryptionService,
- never exposed by public settings API,
- export/backup follows encryption policy,
- logs and exception context redact it,
- UI does not redisplay the stored value,
- uninstall clearly defines secret handling.

Manifest and committed config contain no real credentials.

---

## 17. Testing

Mandatory minimum by capability:

### Every extension package

- valid/invalid manifest fixtures,
- path traversal/Zip-Slip/symlink tests,
- forbidden function/token variants,
- oversized and MIME mismatch fixtures,
- import rollback,
- compatibility failure.

### PHP plugin

- unique namespace and autoload test,
- hook registration/payload/error policy,
- route permission/CSRF/rate-limit matrix,
- boot/enable/disable/uninstall,
- no secret leakage.

### Frontend

- entry/loader contract,
- typed API calls,
- XSS/URL sanitization,
- theme-token compatibility,
- accessible keyboard behavior,
- build failure reporting.

### Theme/shortcode

- template/schema validation,
- output sanitization,
- missing slot/fallback,
- contrast/axe smoke,
- cache invalidation.

A temporary test extension must use a unique ID and namespace. A duplicate class from the bundled `hello-widget` caused [ISS-075](../ISSUES.md#iss-075); test fixtures therefore use an ID such as `ping-demo`.

---

## 18. Author review checklist

- [ ] ID, directory, and namespace match.
- [ ] Manifest has valid version and realistic compatibility.
- [ ] Every PHP file has `strict_types=1`.
- [ ] No forbidden functions, dynamic include, or obfuscation.
- [ ] Hooks come only from the catalog.
- [ ] Routes have explicit auth/permission/CSRF policy.
- [ ] Data uses namespaced storage and backup contract.
- [ ] Secrets are in encrypted settings, not code/manifest.
- [ ] Outbound uses an SSRF-safe provider/client.
- [ ] Frontend follows build model, central API, and semantic tokens.
- [ ] Tests use a unique namespace.
- [ ] README documents configuration, risks, upgrade, and uninstall.
- [ ] Import/enable/rollback was verified on a clean instance.

---

## 19. Operator checklist

- [ ] Package source is verified.
- [ ] Backup is created and verified.
- [ ] Import completed without manually bypassing policy.
- [ ] Plugin remained disabled after import.
- [ ] Manifest and capabilities were reviewed.
- [ ] Enable was tested in staging first.
- [ ] Health/routes/content smoke tests are green.
- [ ] Logs, audit, and outbound traffic show no anomaly.
- [ ] Frontend build/redeploy was performed when required.
- [ ] A rollback procedure exists.

---

## 20. Related documents

- [Plugin architecture](../architecture/PLUGINS.md)
- [Theme architecture](../architecture/THEMES.md)
- [Events and hooks](../architecture/EVENTS.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Code Editor](../user/CODE_EDITOR.md)
- [Developer Mode](../user/DEVELOPER_MODE.md)
- [Plugin user guide](../user/PLUGINS.md)
