---
title: Settings
description: Schema-driven flat-file settings, precedence, visibility, and secrets
icon: material/tune-variant
---

# PaginiumCMS Settings

> **Authority:** `SettingsSchema` defaults + flat-file overrides  
> **Current file:** `backend/storage/app/content/data/settings.json`  
> **Test isolation:** `settings.testing.json`

The settings engine provides typed, validated, and auditable configuration without a database. It stores only deviations from defaults, allowing new safe defaults without rewriting the whole file. Sensitive values must not leak into the public API, logs, or plaintext backup reports.

---

## 1. Layers and precedence

Canonical effective-value model:

```text
schema default
→ persisted instance override
→ environment/secret binding for explicitly permitted fields
→ non-persisted runtime request context
```

The environment must not arbitrarily override every UI setting. For each field, the schema defines whether it can be:

- changed through the admin API,
- bound from the environment,
- published in the public slice,
- encrypted,
- changed without restart,
- owned by a module.

When sources conflict, diagnostics show the effective source without revealing the secret.

---

## 2. Storage model

`settings.json` contains only overrides:

```json
{
  "general": {
    "siteName": "My site",
    "language": "en"
  },
  "content": {
    "autoSaveInterval": 120
  }
}
```

Write rules:

1. load schema and current overrides under a lock,
2. verify group/field ownership and permission,
3. validate type, range, enum, URL, and cross-field invariants,
4. encrypt sensitive fields,
5. remove values equal to defaults when semantics are unchanged,
6. atomically write JSON,
7. reload only affected runtime services,
8. create an audit/event without the secret value.

An invalid or damaged settings file must not be silently replaced with defaults without an incident and backup of the original.

---

## 3. Field classification

| Class | Example | API behavior |
|-------|---------|--------------|
| **Public** | site name, logo, locale, maintenance copy | may appear in `/api/settings/public` |
| **Admin** | pagination, editor, retention, alert policy | authorized admin read/write only |
| **Restricted** | access control, path ACL, developer policy | SUPER_ADMIN or dedicated permission |
| **Secret** | SMTP password, API token, webhook secret | write-only/redacted; encrypted at rest |
| **Environment-owned** | `APP_KEY`, runtime paths, trusted proxies | not editable through normal settings UI |
| **Derived** | capability status, source of effective value | computed, not persisted as an override |

The public endpoint uses a field allow-list. It must not serialize a whole group and then remove “known secrets,” because a newly added secret field could leak.

---

## 4. Settings groups

### Current groups

| Group | Purpose | Typical editor |
|-------|---------|----------------|
| `general` | site identity, locale, timezone, registration | ADMIN+ |
| `branding`, `appearance` | logo, favicon, public/login appearance | ADMIN+ |
| `accessControl` | role permissions, path ACL | SUPER_ADMIN |
| `content` | pagination, default status, autosave, lock TTL | ADMIN+ |
| `maintenance` | coming-soon/maintenance modes | ADMIN+ |
| `editor` | editor, spellcheck, tab size | ADMIN+ |
| `smtp` | mail transport | ADMIN+, secret fields |
| `notifications` | toast and UI behavior | ADMIN+; only a safe public slice |
| `connectors` | email, ntfy, Discord, Telegram, webhook | ADMIN+, secret credentials |
| `monitoring` | incidents and scheduled reports | ADMIN+ |
| `security` | password/2FA policy | ADMIN+ or restricted field |
| `firewall` | WAF rules and thresholds | ADMIN+ |
| `logging` | severity, retention, request logging | ADMIN+ |
| `marketing` | demo footer link and URL | ADMIN+ |

### Planned Hybrid Engine groups

It.68–77 should use one shared hierarchy, for example:

- `engine.deploymentMode`
- `engine.storage.driver`
- `engine.cache.driver`
- `engine.git.enabled`
- `engine.git.publishStrategy`
- `engine.performanceGuard.enabled`
- `media.storage.driver`
- `localization.*`
- `translation.providers.*`
- `ai.providers.*`
- `apiAuth.*`

Exact names are locked in the schema. Missing keys must produce safe **Classic** behavior.

---

## 5. Schema contract

Each field should define at minimum:

```json
{
  "type": "string",
  "default": "file",
  "enum": ["file", "redis"],
  "visibility": "admin",
  "secret": false,
  "restartRequired": false,
  "owner": "core.cache",
  "since": "2.1.0"
}
```

The schema may also include label/help keys, validation bounds, capability dependencies, deprecation, and migration callbacks. The UI is schema-driven, but the backend schema is authoritative; frontend validation is only a UX aid.

Cross-field examples:

- `engine.cache.driver=redis` requires valid Redis configuration and a successful capability probe,
- Git publishing cannot be enabled without repository/branch/credential policy,
- a cloud translation provider requires an encrypted credential and outbound allow-list,
- `pathAclEnabled=true` requires valid rules JSON.

---

## 6. API

| Method | Endpoint | Access | Purpose |
|--------|----------|--------|---------|
| `GET` | `/api/settings/public` | anonymous or session | allow-listed public effective values |
| `GET` | `/api/admin/settings` | ADMIN | schema + redacted effective values |
| `GET` | `/api/admin/settings/{group}` | by owner | one group |
| `PUT` | `/api/admin/settings/{group}` | by owner | validate and store patch/replace by contract |
| `DELETE` | `/api/admin/settings` | ADMIN | reset supported overrides; restricted groups by policy |
| `GET` | `/api/settings/public-demo` | only when `DEMO_MODE=true` | demo login copy |

An API response should distinguish:

- `value` or a redacted marker,
- `source` (`default`, `file`, `env`),
- `editable`,
- `restartRequired`,
- validation errors per field.

A secret endpoint never returns plaintext. An unchanged password input uses a separate “keep existing” state rather than submitting `********` as a new password.

---

## 7. Secrets and encryption

Sensitive fields use `EncryptionService` and application key material. Required rules:

- ciphertext is a versioned format with algorithm/key-version metadata,
- `APP_KEY` or the master key is not stored in the settings file,
- rotation supports dry-run, backup, and rollback,
- decrypt failure does not clear the credential or store a default,
- logs contain only field/provider name and incident ID,
- test fixtures do not use production keys or secrets,
- export/backup clearly states that restore requires the correct key material.

---

## 8. Runtime reload

Not every setting is applied in the same way:

| Type | Behavior |
|------|----------|
| UI/public copy | immediate context/cache refresh |
| RBAC/path ACL | atomic save + policy-store synchronization + audit |
| logging/notification policy | reload factory/service on a later request |
| cache driver | capability test, controlled switch, fallback |
| storage/media driver | migration workflow, not an instant toggle |
| trusted proxies/session cookie/key material | environment/deployment change, usually restart |

For `restartRequired` or migration-required settings, the UI must show actual runtime state rather than success merely because JSON was stored.

---

## 9. Module ownership

The Core schema registry aggregates groups, but each module owner defines fields and domain validation. A module must not modify another group's fields through an undocumented array merge.

When an extension is disabled/uninstalled, its settings:

- are not automatically deleted without confirmation,
- are not published through the public API,
- are marked orphaned/disabled,
- may be exported or purged according to policy.

---

## 10. Migrations and deprecation

Renaming or changing a field type requires:

1. schema version,
2. idempotent migration,
3. dry-run/report,
4. backup of original overrides,
5. compatibility read during a deprecation window,
6. audit without secret values,
7. rollback or restore procedure.

The schema should not retain three aliases for the same field forever. After the deprecation window, the legacy key is removed through a controlled migration.

---

## 11. Validation and errors

The backend uses the shared validator and maps validation failures to 422 with field details. 403 means missing permission, 409 means a settings revision conflict or active migration, and 503 means an unavailable capability.

Settings writes should use a revision/ETag or lock so two admin forms cannot silently overwrite each other. This is a target hardening requirement even when the current repository only prevents filesystem races with `flock`.

---

## 12. Tests

- default + override merge,
- isolation of `settings.testing.json`,
- public allow-list and secret non-disclosure,
- encryption round-trip and wrong-key failure,
- permission per group/field,
- cross-field validation,
- concurrent update/conflict,
- runtime reload and restart-required marker,
- capability-probe failure preserves the previous driver,
- schema migration dry-run/rollback,
- SK/EN label/help catalog parity.

---

## Related documents

- [CORE.md](./CORE.md)
- [STORAGE.md](./STORAGE.md)
- [CORE_HARDENING.md](./CORE_HARDENING.md)
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md)
- [ITERATION_68](../ITERATION_68.md)
