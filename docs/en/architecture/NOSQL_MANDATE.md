# No-SQL mandate — immutable project rule

> **Status:** canonical rule  
> **Applies to:** every iteration, deployment mode, and future enterprise-ready feature  
> **Governing principle:** files remain the primary PaginiumCMS source of truth

---

## Rule

PaginiumCMS **MUST NOT** use an SQL database or an external document database such as MySQL, PostgreSQL, or MongoDB as the **primary source of truth** for:

- CMS content,
- configuration,
- users and permissions,
- security state,
- operational data that belongs to the project's file model.

Primary data must remain in portable files that can be backed up, versioned, audited, and restored without a mandatory database service.

### Allowed persistence forms

| Category | Format | Typical location |
|----------|--------|------------------|
| Content | Markdown with YAML front matter, JSON documents | `storage/app/content/pages/`, `blog/`, … |
| Metadata and settings | JSON | `data/settings.json`, `data/index/*.json` |
| Users and security state | JSON with sensitive fields encrypted at rest | `data/users/`, audit stores |
| Media binaries | Files on disk or object storage | `media/`, optionally S3 through a driver |
| Derived indexes | JSON built from source files | `data/index/content.json` |
| Cache | File, memory, APCu, or Redis | **Derived only — never SSOT** |

**Redis, APCu, and in-memory layers** are permitted only for:

- caching,
- temporary coordination,
- rate limiting,
- an optional session store for horizontally scaled deployments,
- job queues,
- locks or short-lived operational counters.

Their contents must be disposable and rebuildable without losing primary CMS data.

---

## Source of truth versus derived layers

| Layer | May contain unique primary data? | Must be rebuildable? |
|-------|----------------------------------|----------------------|
| JSON / Markdown / YAML documents | ✅ Yes | From backup or Git history |
| File-based user and settings metadata | ✅ Yes | From backup |
| Index | ❌ No | ✅ From source documents |
| Cache | ❌ No | ✅ From source documents or API responses |
| Redis queue | Temporary processing state only | ✅ Jobs need safe retry or a persistent file record |
| Git remote | Distribution or backup copy, depending on mode | It must not become an undocumented sole authority |
| Media object storage | May be authoritative for binary files | Metadata and relationships remain in the CMS file model |

---

## What this rule does not forbid

- **Index files**, such as `content.json` or aggregated user listings, when generated from source documents.
- **Git as a distribution and versioning mechanism**, while the file contract remains intact.
- **S3 or compatible object storage** for media binaries through a driver.
- **External APIs**, such as GitHub, webhooks, SMTP, ntfy, or translation services, when they do not become primary CMS storage.
- **Redis cache**, rate limiting, job queues, or temporary coordination.
- **Optional integration modules** that export or synchronize data to a third-party database while the Core remains fully functional without them.

---

## Forbidden architectural shortcuts

The following designs violate the mandate:

- storing content only in SQL tables and generating Markdown files only during export,
- requiring MongoDB or Elasticsearch to read ordinary content,
- storing user accounts only in Redis or an external database without an authoritative file record,
- using a cache from which the system cannot safely fall back to file data,
- adding a Core feature that stops working when a mandatory database service is disconnected,
- performing a migration that removes the file model without an explicit project decision.

---

## Proposal review gate

A proposal that introduces SQL or a mandatory external database for Core data must be **rejected** unless all of the following are true:

1. it is explicitly scoped as an **optional third-party integration**, not a mandatory Core dependency,
2. the file source of truth remains the default, complete, and documented path,
3. the Core, administration interface, and API remain usable without the integration,
4. export or synchronization defines conflict handling, retries, and recovery,
5. the proposal passes architecture and security review,
6. `PHILOSOPHY.md` or this mandate changes only through an explicit project decision, not as a side effect of implementation.

---

## Testable requirements

An implementation respects the No-SQL mandate only when:

- deleting the cache does not destroy primary data and the system can recover from files,
- every derived index can be rebuilt completely,
- a backup of primary files contains all required CMS data,
- the local Classic mode requires neither Redis nor a database,
- new drivers do not change the domain document contract,
- diagnostics can distinguish a damaged source document from a stale index or cache.

---

## Related documents

- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md) — layered architecture above the file SSOT
- [DEPLOYMENT_MODES.md](./DEPLOYMENT_MODES.md) — Classic, Hybrid, and Git-headless modes
- [STORAGE.md](./STORAGE.md) — physical storage layout
- [../PHILOSOPHY.md](../PHILOSOPHY.md) — project philosophy and decision framework
