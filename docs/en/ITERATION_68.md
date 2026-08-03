# Iteration 68 — Hybrid Engine foundation

> **Status:** ✅ complete (Hybrid Engine foundation — It.68)  
> **Priority:** 🔴 critical path  
> **Wave:** [Hybrid Engine HE-1](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Rules:** [No-SQL mandate](architecture/NOSQL_MANDATE.md) · [Hybrid Engine](architecture/HYBRID_ENGINE.md)

## Goal

Introduce a **storage abstraction, document schema registry, and engine settings** so later deployment modes and drivers do not require controller rewrites. Existing flat-file behavior remains the default and reference outcome.

It.68 is not a migration to a new storage product. It creates a controlled architectural seam over the existing repositories.

---

## Decisions

| Topic | Decision |
|-------|----------|
| Source of truth | local JSON/Markdown/YAML documents remain authoritative |
| Default | `deploymentMode=classic`, `storageDriver=local` |
| Migration | incremental vertical slices; no big-bang rewrite |
| Schemas | JSON Schema for admin-written documents; invalid writes fail closed |
| Compatibility | a missing `engine` group is interpreted as Classic/local |
| Secrets | the public `engine` settings slice contains no secrets or internal paths |

---

## Backend scope

| Component | Contract |
|-----------|----------|
| `Core/Storage/Contracts/StorageInterface.php` | `read`, `write`, `exists`, `delete`, `list`; logical paths rather than arbitrary filesystem paths |
| `Core/Storage/Drivers/LocalFlatFileStorage.php` | delegates to existing safe reader/writer/repository services |
| `Core/Storage/StorageFactory.php` | resolves allow-listed drivers; an unknown driver produces a safe error rather than a dynamic class name |
| `Core/Validation/DocumentSchemaRegistry.php` | registers a schema by document type and version |
| `Core/Validation/DocumentValidator.php` | maps validation failures to a stable API contract |
| DI wiring | settings and one content write slice first; expansion only after parity tests |
| diagnostics | capability report, driver status, and rebuild guidance without leaking sensitive paths |

### Minimum storage contract

The implementation must preserve:

- normalized paths and allow-listed roots,
- rejection of `..`, symlink escapes, and null-byte paths,
- atomic temp write → `fsync` where supported → rename,
- `flock` and the existing lock model,
- stable domain exceptions instead of raw filesystem errors,
- the same resulting JSON and metadata as before the abstraction.

The interface must not pretend to provide a distributed transaction. Index, cache, and publish operations remain separate domain-service steps.

---

## Settings

Draft schema:

```yaml
engine:
  deploymentMode: classic        # classic | hybrid | git_headless
  storageDriver: local           # local only in It.68
  schemaValidationEnabled: true
  capabilityProbeEnabled: true
```

Rules:

- The It.68 UI enables only `classic`/`local`.
- Later values may be shown as “not installed,” not as working switches.
- An invalid value must not activate an experimental driver; the application emits an explicit diagnostic error or uses a documented Classic fallback during bootstrap.
- A setting change is audited.

---

## Frontend

Settings → **Engine** contains:

1. the current deployment mode and storage driver,
2. a capability probe explaining availability,
3. locked future profiles without a false promise,
4. links to diagnostics and documentation,
5. an SK/EN `engine` i18n module following the existing pattern.

The frontend never sends an internal class name or driver path.

---

## Schema and migration

The first supported schema should cover a document already edited as JSON by an administrator, such as settings or a plugin manifest. Rollout:

1. register the schema and its `schemaVersion`,
2. run read-only report validation over existing data,
3. fix or explicitly grandfather legacy deviations,
4. enable fail-closed validation for new writes,
5. only then migrate another document type.

Storage-layer migration:

1. `LocalFlatFileStorage` delegates to existing code,
2. settings read/write moves through the interface,
3. a parity test compares the old and new path,
4. content write moves only after parity is confirmed,
5. rollback restores the previous DI binding without converting data.

---

## Out of scope

- Redis as storage SSOT,
- Git as primary storage,
- S3 for content metadata,
- API keys or JWT,
- rewriting every repository at once,
- loading an arbitrary driver class from user input.

---

## Tests

- `StorageFactoryTest`: default local, unknown driver, safe bootstrap.
- `LocalFlatFileStorageTest`: parity, atomic writes, flock, traversal, symlink escape.
- `DocumentSchemaRegistryTest`: known/unknown schema, version mismatch.
- API test: invalid admin JSON → `422` with stable field errors.
- Regression: settings and content output before/after the abstraction are equivalent.
- Recovery: an interrupted temp write preserves the last valid version.
- Full gate with no behavior change for a Classic installation.

---

## Definition of Done

- [x] `StorageInterface` and the local driver are in the production path for settings and one content write slice.
- [x] The driver factory uses an allow-list and safe defaults.
- [x] At least one admin document has versioned JSON Schema validation (`settings.overrides@1`).
- [x] The capability probe distinguishes available, unavailable, and failing capabilities.
- [x] Missing `engine.*` preserves `beta.23` behavior.
- [x] Migration dry-run, rollback, and incident scenarios are documented (see below).
- [x] SK/EN architecture, API/settings documentation, and changelog are updated.
- [x] `iteration-gate.sh` and the Classic smoke test are green.

---

## Migration dry-run, rollback, and incidents

| Scenario | Expected behavior |
|----------|-------------------|
| Missing `engine.*` keys | Bootstrap uses `classic` + `local`; no settings migration required |
| Invalid `settings.json` group shape | Fail closed on read/write (`422` / `ValidationException`); admin must repair the file |
| Unknown storage driver in settings | `StorageFactory` falls back to `local` at bootstrap; strict factory mode throws |
| Interrupted atomic write | Previous valid file remains (temp file discarded on failed rename) |
| Rollback It.68 code | Restore prior DI bindings in `services.php`; flat-file data needs no conversion |
| Enable hybrid/git before It.69+ | UI shows capability `unavailable`; active mode stays `classic` |

Storage rollout order used in It.68:

1. Contracts + `LocalFlatFileStorage`
2. Settings read/write through `StorageInterface`
3. Parity tests (`SettingsStorageParityTest`)
4. JSON content writes through storage (Markdown unchanged)
5. JSON Schema for `settings.overrides` before accepting new writes

## Follow-ups

[ISS-121](../ISSUES.md#iss-121) · [ISS-122](../ISSUES.md#iss-122) · [ISS-123](../ISSUES.md#iss-123) — defects found and fixed during It.68 implementation (pre-release).

[It.69 cache](ITERATION_69.md) · [It.70 Git publish](ITERATION_70.md) · [It.72 media drivers](ITERATION_72.md) · [It.73 locale model](ITERATION_73.md) · [It.74 auth](ITERATION_74.md)
