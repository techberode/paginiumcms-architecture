# Iteration 72 — media storage drivers

> **Status:** ✅ MVP complete (local driver + probe)  
> **Priority:** 🟡  
> **Wave:** [Hybrid Engine HE-5](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.68](ITERATION_68.md)  
> **Follows:** It.24 DAM

## Goal

Decouple media binaries from a specific filesystem through **Flysystem or an equivalent contract**. Local disk remains the default; an optional S3-compatible driver enables object storage and CDN delivery without changing the Media API.

Important distinction:

- `registry.json`, alt text, folders, relationships, and workflow metadata remain flat-file SSOT,
- with `local`, the authoritative binary object is a local file,
- with `s3`, the authoritative binary object is in the configured object store; this is still not an SQL database or a replacement for content documents.

---

## Contract

| Component | Responsibility |
|-----------|----------------|
| `MediaStorageDriverInterface` | `put`, `readStream`, `delete`, `exists`, `checksum`, `publicUrl` |
| `LocalMediaStorageDriver` | parity with the existing `MediaRepository` |
| `S3MediaStorageDriver` | S3-compatible adapter using a supported SDK/Flysystem driver |
| `MediaStorageFactory` | allow-list `local | s3`; safe local default |
| `MediaUrlResolver` | stable API/public URL contract without leaking an internal bucket key |
| `MediaMigrationService` | copy → checksum verify → registry update → optional source cleanup |
| CLI | `media:storage:probe`, `media:migrate`, `media:migrate:verify`, `media:migrate:rollback` |

The server derives a logical object key from media identity. A client never submits a raw filesystem path or arbitrary S3 key.

---

## Settings

```yaml
media:
  storageDriver: local
  s3:
    endpoint: null
    region: null
    bucket: null
    keyId: null
    secret: null
    pathStyle: false
    publicBaseUrl: null
    visibility: private
```

- `secret` and credentials use `EncryptionService` and password fields.
- A custom endpoint passes `OutboundUrlGuard`; a private/LAN endpoint requires an explicit administrator allow-list policy.
- Bucket, region, and endpoint are validated server-side.
- `publicBaseUrl` rejects `javascript:` and invalid schemes.
- The capability probe never logs a secret or signed URL.

---

## URL and access model

| Mode | Recommendation |
|------|----------------|
| Public media | stable CDN/public URL or API redirect according to policy |
| Private media | short-lived signed URL generated after ACL check |
| Admin preview | session authorization; signed URL must not be cached long-term |
| Driver migration | content documents use media ID, not a hardcoded bucket URL |

This avoids rewriting every article during a local-to-S3 migration. The resolver maps a stable media ID to the current URL.

---

## Upload and security

The existing `UploadSecurityValidator` remains in front of the driver. A driver must not weaken:

- MIME/content sniffing and extension policy,
- size limits and quotas,
- image decode/re-encode rules when enabled,
- path traversal and executable-upload controls,
- malware scanning hooks,
- audit and `media:write` permission.

S3 metadata and user-defined headers are allow-listed. The server does not accept arbitrary `Content-Disposition` or cache headers from a client.

---

## Migration

Safe migration procedure:

1. probe the destination and run a temporary write/read/delete test,
2. create a read-only inventory and size estimate,
3. copy in batches without changing the active driver,
4. verify checksum/size,
5. store a migration journal,
6. switch the driver after confirmation,
7. smoke-test URLs and permissions,
8. delete the local source only after a separate retention window.

Rollback uses the journal and preserved local files. A partial migration must not create unreadable media IDs; the migration tool may use an explicit dual-read mode during transition, but not as a permanent ambiguous configuration.

---

## Frontend

Settings → Media → Storage provides:

- driver and capability state,
- connection test,
- explanation of private/public URL policy,
- migration dry-run and progress,
- mandatory confirmation before cutover,
- clear rollback state.

The media picker and content editor use the same media ID regardless of driver.

---

## Out of scope

- moving content JSON/Markdown to S3,
- SQL asset registry,
- multi-region replication,
- video transcoding pipeline,
- automatic deletion of local originals without retention,
- accepting an arbitrary bucket key from an API client.

---

## Tests

- shared driver contract over local and memory/mock S3 adapters,
- local parity with the current media suite,
- traversal and malicious keys are rejected,
- private media requires ACL and signed URL,
- public URL resolver for local/S3,
- migration copy + checksum + resume after interruption,
- rollback from journal,
- secret redaction in API/logs,
- S3 outage does not damage the registry or existing local data,
- Classic/local has no S3 dependency.

---

## Definition of Done

- [x] `local` is the default and behaves as it did before It.72.
- [ ] S3-compatible staging upload/read/delete passes contract tests.
- [x] The metadata registry remains flat-file SSOT.
- [ ] Media ID is independent from the physical URL.
- [ ] Migration provides dry-run, journal, checksum, resume, and rollback.
- [x] Private/public policy, SSRF, and secret handling are tested (MVP: probe redaction, settings validation, local-only path).
- [x] SK/EN user, architecture, and deployment documentation is updated (MVP scope).

## Related

[It.24 DAM](ITERATION_24.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
