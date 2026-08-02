---
title: Plugins and External Extensions
description: Security and runtime boundaries of the PaginiumCMS extension system
icon: material/puzzle-outline
---

# Plugin and External Extension Architecture

> **Status in `v2.1.0-beta.23`:** the backend plugin runtime, ZIP import, flat-file registry, lifecycle, and hook emitters are implemented.  
> **Important limitation:** frontend extension code bundled by Vite cannot be loaded automatically from an arbitrary ZIP without a build/redeploy step.

A plugin extends PaginiumCMS without modifying platform Core. It still runs inside the same PHP process as the CMS, so “plugin” does not mean a secure sandbox. The architecture therefore relies on constrained placement, a manifest, fail-closed import, allow-listed hooks, explicit route middleware, and an audited lifecycle.

---

## 1. Terminology and boundaries

| Term | Meaning |
|------|---------|
| **Core** | platform primitives and invariants; external code may not write here |
| **Internal module** | trusted domain capability shipped with the CMS |
| **Plugin / extension** | optional external package imported and managed through the extension lifecycle |
| **Theme package** | future presentation package; not the same as an implemented color scheme |
| **Hook** | allow-listed extension point emitted by the platform |
| **Extension route** | Slim route owned by a plugin and loaded only while it is enabled |

An external plugin does not become an internal module merely because it contains many files. It is not entitled to internal Core namespaces, bootstrap, vendor, a generic filesystem, or unrestricted DI container access.

---

## 2. Canonical placement

```text
backend/app/Http/Extensions/{plugin-id}/
├── plugin.json
├── src/
├── routes.php             # optional; imported into the central route layer
├── assets/                # optional static assets
├── frontend/              # FE source; requires a supported build/deploy flow
├── README.md
└── tests/                 # recommended

backend/app/Http/Routes/extensions/{plugin-id}.php
frontend/src/extensions/{plugin-id}/
data/plugins.json
```

Forbidden targets:

```text
backend/app/Core/
backend/bootstrap/
backend/vendor/
data/ outside PluginRegistry or a documented namespaced storage contract
any path outside the project root
```

Moves or symlinks must not bypass canonical-path checks. A ZIP entry containing an absolute path, `..`, a NUL byte, or a symlink escape is rejected.

---

## 3. Currently implemented components

| Component | Responsibility | Status |
|-----------|----------------|--------|
| `PluginRegistry` | flock-protected `data/plugins.json` flat-file registry | ✅ |
| `PluginImporter` | temporary extraction, validation, and atomic move | ✅ |
| `PluginPolicyScanner` | scan of every imported file | ✅ |
| `ExtensionManifestValidator` | identity, version, hooks, and compatibility | ✅ |
| `PluginManager` | list/import/enable/disable/uninstall/boot | ✅ |
| `HookManager` + `HookEmitter` | catalog hook registration and emission | ✅ |
| extension route bootstrap | loads routes of enabled plugins | ✅ |
| `/api/admin/extensions/*` | administrative lifecycle API | ✅ |
| `ExtensionsManager` | administrative user interface | ✅ |
| `hello-widget` | reference plugin and test contract | ✅ |
| runtime FE loader for arbitrary ZIPs | safe loading of a prebuilt external UI | ⏳ open contract |

Documentation must not confuse `frontend/src/extensions/loader.ts` with a full runtime marketplace loader. `import.meta.glob` is resolved at build time; adding a source directory on production does not add it to the existing JavaScript bundle.

---

## 4. The `plugin.json` manifest

Minimal manifest:

```json
{
  "id": "hello-widget",
  "name": "Hello Widget",
  "version": "1.0.0",
  "minCmsVersion": "2.0.54",
  "description": "Reference extension",
  "author": "PaginiumCMS",
  "hooks": {
    "content.after_save": "PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onContentAfterSave"
  },
  "routes": true,
  "frontend": false
}
```

Mandatory rules:

- `id` is kebab-case and matches the directory name,
- `version` is valid SemVer,
- `minCmsVersion` is compared with the application version,
- hooks must exist in `HookCatalog`,
- declared capabilities must have corresponding files,
- unknown required fields or invalid types cause rejection,
- the manifest contains no secrets, access tokens, or absolute local paths.

Future manifest evolution should use a versioned schema such as `manifestVersion`, not silently change the meaning of existing fields.

---

## 5. Import pipeline

```text
upload ZIP
  → size/MIME limits
  → temporary directory
  → Zip-Slip/symlink checks
  → manifest schema and identity
  → syntax + security + code policy scan
  → compatibility and collision checks
  → atomic move
  → registry write with enabled=false
  → audit
```

Rules:

1. A successfully imported plugin starts **disabled**.
2. Validation is fail-closed; a partially copied plugin must never remain active.
3. The registry is written under a lock using a temporary-file-plus-rename pattern.
4. An existing ID is not overwritten without an explicit upgrade flow.
5. Import and activation are separate security events.
6. Policy failures return structured `422`; malformed manifests typically use `400/422` according to the API contract.
7. Reports must not expose secret content or host filesystem paths.

---

## 6. Lifecycle and state model

Recommended states:

```text
absent → imported_disabled → enabled
                   ↘ incompatible
                   ↘ failed_validation

enabled → disabled → enabled
disabled → upgrading → disabled|failed_upgrade
disabled → uninstalling → absent
```

The current registry may use a simpler `enabled` boolean, but the application service must still distinguish import, enable, disable, and uninstall failures. The admin UI must not report success when the registry changed but PHP boot or route loading failed.

### Enable

1. revalidate the manifest and compatibility,
2. load plugin classes deterministically,
3. register only declared hooks,
4. expose declared routes,
5. emit `extension.enabled` and `extension.boot` at boot,
6. persist an audit record.

### Disable

Disable removes runtime registrations at the next safe boot/reload boundary. Plugin data is retained. A public route or hook must not remain active because an old registry value was cached.

### Uninstall

Uninstall is separate from disable and requires confirmation. Before deletion, the CMS should determine whether the plugin owns data, an export, or a cleanup handler. The CMS must not execute an untrusted uninstall PHP script with unrestricted authority.

---

## 7. Hook contract

A plugin subscribes only through its manifest. Calling `HookManager::add()` directly from an arbitrary bootstrap file is forbidden.

The implemented catalog includes, for example:

| Hook | Meaning |
|------|---------|
| `content.before_save` | validated attempt before content persistence |
| `content.after_save` | successfully persisted content |
| `content.after_delete` | successful delete/soft-delete according to workflow |
| `content.after_status_change` | lifecycle status transition |
| `content.after_scheduled_publish` | successful scheduled publication |
| `extension.boot` | boot of an enabled plugin |
| `extension.enabled` | successful activation |
| `extension.disabled` | successful deactivation |

A handler should receive a minimal, versionable payload and must not mutate a Core object through a shared mutable reference. A `before_*` hook needs an explicitly documented failure policy; an `after_*` hook must not pretend that an authoritative write did not already occur.

Internal events and public hooks have different stability rules. See [EVENTS.md](./EVENTS.md).

---

## 8. Routes and authorization

An extension route must use the same security primitives as Core API routes:

- validated route input,
- explicit public/admin classification,
- session + CSRF for cookie-authorized mutations,
- RBAC/path ACL or a future explicit It.74 scope,
- rate limiting for public or expensive endpoints,
- the standard API error contract,
- no implicit access merely because the route belongs to an enabled plugin.

The route file must return the supported registrar contract and may not alter the global middleware stack, exception handler, or DI definitions outside the permitted extension API.

**API↔FE LAW:** endpoint → typed client → user-facing consumer → documentation/contract test. A plugin route may legitimately be headless, but its manifest or README must say so explicitly.

---

## 9. Frontend extensions

Current source model:

```text
frontend/src/extensions/{id}/
├── index.ts
├── api.ts
├── components/
└── tests/
```

Rules:

- strict TypeScript and the centralized API client,
- no secrets in the bundle,
- CSS namespacing or approved design tokens,
- no patching of Core source files,
- route/menu/slot registration only through a documented FE extension contract,
- HTML and URL sanitization equivalent to Core UI.

### Current build boundary

Vite source discovery happens at build time. Safe models are therefore:

1. **bundled extension:** the plugin is part of the repository and release build,
2. **self-host rebuild:** import marks that a controlled rebuild/redeploy is required,
3. **future signed runtime bundle:** separate manifest, integrity hash, CSP-compatible loader, and compatible ABI.

Model 3 is not declared implemented in Public Beta. Dynamic `eval`, inline script injection, and loading unverified remote JavaScript are forbidden.

---

## 10. Data and storage

A plugin must not write directly to an arbitrary `data/` file. The target contract is a namespaced repository/storage key, for example:

```text
extensions/{plugin-id}/settings
extensions/{plugin-id}/content/{document-id}
extensions/{plugin-id}/state/{key}
```

Authoritative plugin data must be included in backup/export. Cache, index, or build output must be marked rebuildable. Plugin settings use schema registration; secrets remain encrypted/write-only and are excluded from the public settings slice.

---

## 11. Security model

Code policy reduces risk but does not create a PHP sandbox. Therefore:

- only a privileged administrator installs plugins,
- import remains untrusted even for a local ZIP,
- forbidden functions and token scans are a baseline, not proof of safety,
- no generic shell, subprocess, `eval`, dynamic include, unrestricted outbound access, or generic filesystem,
- outbound requests use SSRF policy and an allow-listed provider contract,
- audit records actor, plugin ID/version, action, and result,
- activation may require recent 2FA/OTP according to security policy,
- production should prefer signed releases and controlled sources.

If strong isolation becomes necessary, it requires a process/container boundary or capability RPC. A namespace and regex scanner are not enough.

---

## 12. Upgrade and compatibility

Upgrade must be a dedicated workflow:

1. validate the new package,
2. compare ID, version, and `minCmsVersion`,
3. back up the old package and plugin data,
4. disable or enter a maintenance gate as appropriate,
5. atomically replace files,
6. run only a declared, constrained data migration,
7. smoke-test boot/routes/hooks,
8. commit the registry version or roll back.

Downgrade is not automatically safe. Breaking hook payload, route contract, theme slot, or FE ABI changes require versioning and a deprecation period.

---

## 13. Testing contract

Minimum plugin tests:

- manifest schema and invalid fixtures,
- policy scan and Zip-Slip fixtures,
- import/enable/disable/uninstall lifecycle,
- unique PHP namespaces in tests,
- hook payload and failure behavior,
- route auth/CSRF/permission matrix,
- frontend API/component tests when UI is present,
- compatibility with minimum/maximum supported CMS versions,
- rollback after failed upgrade or boot.

The duplicate-class incident is documented as [ISS-075](../ISSUES.md#iss-075). A test plugin must not reuse the namespace of a bundled reference plugin.

---

## 14. Operational recommendations

- create a verified backup before import,
- test plugins in a dev/staging profile first,
- run health, route, and content-save smoke tests after enable,
- watch PHP logs, audit, and latency,
- perform the supported build/redeploy after FE source changes,
- disable a plugin before upgrade or deep diagnostics,
- never bypass an `incompatible` or policy warning by manually copying files.

---

## 15. Outside the current contract

The following are not guaranteed:

- a secure marketplace,
- cryptographic signing of every plugin,
- a PHP sandbox,
- autonomous runtime installation of React bundles without rebuild,
- arbitrary plugin database migrations,
- access to internal Core classes,
- plugin-controlled auto-publish or AI superuser authority.

---

## Related documents

- [MODULES.md](./MODULES.md)
- [EVENTS.md](./EVENTS.md)
- [THEMES.md](./THEMES.md)
- [API.md](./API.md)
- [Plugin user guide](../user/PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Code Editor](../user/CODE_EDITOR.md)
