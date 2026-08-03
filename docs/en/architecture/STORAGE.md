---
title: Storage
description: Flat-file SSOT, physical layout, and consistency contract
icon: material/database-off-outline
---

# PaginiumCMS Flat-File Storage

> **Mandatory:** [No-SQL mandate](./NOSQL_MANDATE.md)  
> **Current local root:** `backend/storage/app/content/`  
> **It.68 shipped:** `StorageInterface` with the local driver on settings reads and JSON content writes

The storage layer holds authoritative documents and file-based operational state. **SQLite, MySQL, PostgreSQL, or an external document database are not planned as an alternative CMS source of truth.** Optional services may accelerate reads or distribute output, but may not take ownership of primary data.

---

## 1. Data layers

| Layer | Example | Authority | Recovery |
|-------|---------|-----------|----------|
| Primary documents | pages, articles, users, settings | ✅ yes | backup/Git according to policy |
| File operational state | versions, drafts, locks, conflicts, jobs | critical by type | recovery/retention policy |
| Index | `data/index/content.json` | ❌ no | full rebuild from SSOT |
| Cache | memory, file, planned Redis | ❌ no | discard and repopulate |
| Distribution copy | Git remote/static build | ❌ no | republish |
| Binary media | local or planned driver | object is the primary binary asset; metadata remains file-based | driver-specific backup |

---

## 2. Physical layout

```text
backend/storage/
├── app/content/
│   ├── pages/*.md
│   ├── blog/*.md
│   ├── media/
│   │   └── registry.json
│   ├── trash/
│   └── data/
│       ├── users/user_*.json
│       ├── settings.json
│       ├── settings.testing.json
│       ├── index/content.json
│       ├── versions/
│       ├── drafts/{page|article}/
│       ├── locks.json
│       ├── conflicts.json
│       ├── plugins.json
│       └── security/
├── cache/
├── logs/
├── backups/
├── firewall/
└── dev/
```

The exact tree may grow. Every new file still needs:

- an explicit owner,
- a schema/version rule,
- permission and backup policy,
- a safe writer,
- a classification as authoritative or derived.

---

## 3. Content document format

Pages and articles use Markdown with YAML front matter. Canonicalization for revisions and indexing must not change document meaning merely because key order changed. Minimal example:

```markdown
---
title: About us
slug: about-us
status: published
updatedAt: 2026-08-02T10:00:00Z
---

# About us
```

The It.68 schema registry should validate known document types. Unknown fields are handled according to schema version and compatibility policy, not silently dropped.

---

## 4. Safe paths

All local operations pass through `FileValidator`, `FileReader`, `FileWriter`, or their target storage contract. Required rules:

- resolve every path against an explicit base root,
- reject `..`, NUL, unexpected symlinks, and disallowed extensions,
- never concatenate a slug or ID directly into an absolute path without normalization,
- prefer allow-listed roots over blacklists,
- the web server must not expose `data/`, backups, logs, dev, or secrets,
- serve media HTML/SVG with a safe content policy or as an attachment.

The It.68 driver must not weaken these controls. Abstraction is a place to centralize protection, not bypass it.

---

## 5. Atomic writes

Recommended local protocol:

1. validate path, input, and schema,
2. acquire `flock(LOCK_EX)` or a domain lock,
3. read the current state under the same lock for read-modify-write operations,
4. write new content to a temp file on the same filesystem,
5. flush/close and apply safe permissions,
6. atomically replace the target file,
7. release the lock,
8. only then maintain index/cache/events.

Multi-file writes require a migration journal or idempotent repair operation. Rename across different filesystems must not be treated as atomic.

---

## 6. Concurrency and editing

### Pessimistic lock

`data/locks.json` stores resource ID, owner, token, heartbeat, and expiry. The entire registry read-modify-write cycle is protected by `flock`. The token is not shown to other clients and is compared safely.

### Optimistic revision

The client receives a `revision` and sends `baseRevision` on mutation. A mismatch produces HTTP 409 rather than an automatic overwrite. The current SHA-1 fingerprint is a **concurrency fingerprint, not cryptographic proof of integrity**. It must not be used for signatures or security decisions; a future algorithm change must preserve API semantics.

### Draft and conflict

Drafts are separate from the published document. Conflicts are recorded in a bounded flat-file audit log. See [VERSIONING.md](./VERSIONING.md).

---

## 7. Settings and secrets

`data/settings.json` stores only overrides from `SettingsSchema`. Sensitive fields are encrypted before writing through the application encryption service. The public settings slice never includes ciphertext or credential metadata useful to an attacker.

Tests use an isolated `settings.testing.json`. Production secrets must not be read or modified during PHPUnit runs.

---

## 8. Index

`data/index/content.json` is a derived projection for listings, filters, and search metadata. Required contract:

- it can be deleted and fully recreated from source documents,
- it has a schema/version marker,
- writes are atomic,
- rebuild reports invalid or damaged documents,
- a stale/missing index has a defined fallback or explicit service error,
- it does not hold the only copies of secrets or full content unless strictly necessary.

---

## 9. Cache

Memory/file cache is implemented; Redis is planned as an optional It.69 driver. Cache keys must include type, identity, and relevant locale/revision/generation information. Invalidation follows a successful SSOT write.

A cache outage:

- cannot cause data loss,
- cannot automatically enable another external service without a capability test,
- degrades to a supported driver or direct read,
- appears in health/incident reports.

---

## 10. Media

Binary files are currently local and metadata lives in a registry. It.72 adds `MediaStorageDriverInterface` for local/S3-compatible storage.

Immutable rules:

- media metadata and content relationships remain flat-file SSOT,
- a driver cannot create a public URL for a private asset without policy,
- upload validates MIME, extension, size, and name,
- delete/move provides an idempotent recovery path,
- signed URL expiry and CDN configuration are deployment capabilities, not domain data,
- migration between drivers provides dry-run, checksum, and a resumable journal.

---

## 11. Trash, versions, and backup

Soft delete moves a document into `trash/` and stores a sidecar with the original path and timestamp. Restore revalidates the destination, name conflict, and permissions, then rebuilds index/cache state.

Versions and drafts need not share the same retention as live content. Backup policy explicitly defines inclusion of:

- primary documents,
- settings and encrypted secrets,
- user/ACL data,
- media objects or a driver manifest,
- versions/drafts according to retention,
- plugin registry and required extension files,
- migration journals.

Cache, temporary files, and a rebuildable index may be excluded if restore rebuilds the index.

---

## 12. Consistency and recovery

| Incident | Expected response |
|----------|-------------------|
| invalid JSON/front matter | flag the document in diagnostics; do not overwrite it with defaults |
| missing index | rebuild or safe fallback |
| stale cache | invalidate by revision/generation |
| disk full/read-only filesystem | fail before a success response; return an incident ID |
| index failure after SSOT write | keep content stored, mark index stale, retry |
| Git push failure | local state `stored`, publish `failed/pending`, retry |
| partial migration | journal determines resume/rollback; no silent mixed mode |
| damaged lock registry | safe recovery without exposing other lock tokens |

---

## 13. `StorageInterface` (It.68 — shipped)

The contract supports:

- read / write / exists / delete / list on logical paths,
- atomic temp write → `fsync` → rename on real filesystems,
- path normalization, traversal and symlink rejection,
- stable `StorageException` mapping,
- allow-listed driver resolution via `StorageFactory`,
- capability probe for admin diagnostics.

Production path in It.68:

- `SettingsRepository` reads through storage; writes keep `flock` on the settings file handle,
- JSON content saves in `ContentRepository` (Markdown path unchanged),
- default / missing `engine.*` = Classic + local driver.

Rollback = restore previous DI bindings; no flat-file conversion required.

## 14. Operations checklist

- storage roots are not exposed through nginx except a controlled media route,
- file ownership and mode match the PHP-FPM user model,
- backup restore is tested, not merely backup creation,
- index rebuild and cache clear exist through a protected CLI/admin workflow,
- orphan temp/journal files are monitored,
- health checks distinguish writable SSOT, cache, and optional capabilities,
- no documentation proposes an SQL fallback.

---

## Related documents

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md)
- [VERSIONING.md](./VERSIONING.md)
- [SETTINGS.md](./SETTINGS.md)
- [ITERATION_68](../ITERATION_68.md)
- [ITERATION_69](../ITERATION_69.md)
- [ITERATION_72](../ITERATION_72.md)
