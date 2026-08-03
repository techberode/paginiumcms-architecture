# Deployment modes — hosting profiles

> **Parent document:** [HYBRID_ENGINE.md](./HYBRID_ENGINE.md)  
> **Rule:** every mode uses the **No-SQL file source of truth** — [NOSQL_MANDATE.md](./NOSQL_MANDATE.md)

---

## Overview

One codebase supports multiple **deployment profiles**. An administrator selects a profile directly, or the installer recommends one based on project size, team workflow, and available infrastructure.

Profiles are not separate products or editions. They change only the wiring of drivers, cache, Git publishing, and public rendering.

| Profile | Typical project | Source of truth | Index | Cache | Git | Monitoring |
|---------|-----------------|-----------------|-------|-------|-----|------------|
| **Blog / brochure** | Personal site, portfolio, brochure site | Local disk | `content.json` | File / APCu | Off | Basic logs |
| **Marketing / corporate** | Company site, campaign, product site | Local disk | JSON index | APCu / Redis | Optional immediate push | Administration alerts |
| **News / portal** | Editorial team, frequent changes, high traffic | Local disk | JSON index | Redis + HTTP cache | Queued publish | Performance Guard + alerts |

---

## Mode A — Classic flat-file

> **Status:** ✅ current default mode

### Behavior

- The CMS reads from and writes to local file storage directly.
- The index is updated or rebuilt after a successful write.
- Git automation is not required.
- Redis is not required.
- The public site uses the REST API and React SPA.

### Best suited for

- an inexpensive VPS or supported shared hosting,
- a personal website,
- one editor or a small editorial team,
- development and learning installations,
- self-hosting without additional infrastructure services.

### Requirements

- PHP and project dependencies,
- writable `storage/` and `data/` directories,
- a web server or PHP development server,
- optional cron for the scheduler and job queue.

### Fallback

When new `engine.*` keys are missing, the system must behave as Classic. This mode is the compatibility baseline and safe fallback for all other profiles.

---

## Mode B — Hybrid

> **Status:** ⏳ target of It.69–70 (It.68 foundation shipped; Hybrid mode not yet active)

### Behavior

- The source of truth remains on local disk exactly as in Classic mode.
- Reads use an index and read-through cache.
- Cache may use files, APCu, or Redis.
- Publishing may optionally trigger an immediate Git commit and push.
- The public site may remain dynamic or combine dynamic APIs with static sections.

### Best suited for

- marketing and corporate sites,
- small teams using CI/CD,
- projects that need fast reads without changing the No-SQL contract,
- deployments with an optional Redis sidecar,
- content distributed to a repository or build pipeline.

### Requirements

Everything required by Classic mode and, depending on configuration:

- APCu or Redis,
- a Git remote,
- a deploy key or securely stored token,
- a webhook, GitHub Actions, or a custom build process.

### Security rules

- Git credentials are stored encrypted.
- A push occurs only after the local write succeeds.
- A failed Git push must not remove local content.
- Audit records distinguish “saved,” “published locally,” and “distributed through Git.”

---

## Mode C — Git-headless / Jamstack

> **Status:** ⏳ target of It.70 + It.48

### Behavior

- Editors save to the file SSOT in a local worktree or controlled checkout directory.
- Changes may publish immediately or accumulate in a queue.
- In queued mode, a chief editor creates one consistent commit and push.
- A build hook generates a static site or refreshes a headless client.
- The public site may be static HTML, an API-driven SPA, or a hybrid of both.

### Best suited for

- newsrooms and multi-stage approval,
- high-traffic websites,
- separated CMS and presentation infrastructure,
- CDN-first and Jamstack deployments,
- projects that want content versioned and distributed through a Git workflow.

### Requirements

Everything required by Hybrid mode plus:

- the scheduler and job queue — It.29 is already shipped,
- a secure Git worker,
- a build or deployment pipeline,
- a defined conflict strategy,
- monitoring of failed publish jobs.

### Data authority

Git may be a distribution or replication layer, but deployment documentation must identify the authoritative worktree explicitly. The system must not create two undocumented authorities that overwrite one another.

---

## Planned settings

| Key | Values | Iteration |
|-----|--------|-----------|
| `engine.deploymentMode` | `classic` \| `hybrid` \| `git_headless` | It.68 |
| `engine.cache.driver` | `auto` \| `file` \| `redis` \| `memory` | It.69 |
| `engine.git.enabled` | `true` \| `false` | It.70 |
| `engine.git.publishStrategy` | `immediate` \| `queued` | It.70 |
| `engine.git.remote` | URL or named remote | It.70 |
| `engine.git.branch` | branch name | It.70 |
| `site.renderMode` | `dynamic` \| `static` \| `hybrid` | It.48 |
| `engine.performanceGuard.enabled` | `true` \| `false` | It.71 |

Defaults must preserve Classic behavior through safe `??` fallbacks when keys are absent.

---

## Decision guide

| Question | Recommendation |
|----------|----------------|
| Do you want the fewest services and straightforward self-hosting? | **Classic** |
| Do you need Redis or automatic Git push while keeping a dynamic public site? | **Hybrid** |
| Should content pass through commits, review, and an external build? | **Git-headless** |
| Is Redis unavailable? | Classic or Hybrid with file/APCu cache |
| Is Git unavailable? | Classic, or Hybrid without Git distribution |
| Is no worker configured? | Do not enable queued publish |

---

## Nginx and Docker

A host nginx can continue serving the static frontend `dist/` while the PHP API runs in a container or through PHP-FPM. Changing deployment mode does not automatically require a different nginx topology.

Differences are primarily in:

- environment variables,
- `engine.*` settings,
- an optional Redis sidecar,
- the Git worker,
- build hooks and static output.

See [../deploy/DEPLOY.md](../deploy/DEPLOY.md) for details.

---

## Migration between modes

1. Create a full backup and verify restoration.
2. Run content diagnostics and repair damaged documents.
3. Rebuild the index and clear old cache entries.
4. Change `engine.deploymentMode`.
5. Enable the new cache driver and verify fallback without it.
6. For Git mode, configure a test remote or non-production branch.
7. Verify the first commit/push without a production build hook.
8. Only then enable automatic distribution and monitoring.

No SQL migration scripts are needed — no database is the source of truth.

---

## Returning to a previous mode

Rollback must be possible through configuration:

- disable Git publishing,
- stop the worker,
- switch cache to `file` or clear it completely,
- set `engine.deploymentMode=classic`,
- rebuild the index,
- verify local reads and writes.

Changing mode must not require converting documents to another format.

---

## Related documents

- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md) — target architecture
- [NOSQL_MANDATE.md](./NOSQL_MANDATE.md) — immutable data rule
- [../deploy/DEPLOY.md](../deploy/DEPLOY.md) — production runbook
- [../ITERATION_70.md](../ITERATION_70.md) — Git publish implementation
- [../ITERATION_48.md](../ITERATION_48.md) — static output
