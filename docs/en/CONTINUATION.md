# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** August 2, 2026 · `v2.1.0-beta.23`  
> **Active decision:** It.68+ code is paused until the bilingual documentation pass is complete

This document replaces the old chronological “log of everything.” Historical detail remains in [`CHANGELOG.md`](../../CHANGELOG.md), [`ISSUES.md`](ISSUES.md), and individual `ITERATION_*.md` files.

---

## 1. In one sentence

PaginiumCMS is a **No-SQL Hybrid Headless Content Engine**: the React/Vite admin and public site communicate through a Slim REST API and PHP Core, while JSON/Markdown/YAML files remain the mandatory source of truth.

---

## 2. Current state

| Area | Status |
|------|--------|
| Latest documented release | ✅ `v2.1.0-beta.23` — It.58c Layout Switch |
| Public Beta foundation | ✅ functional and continuously hardened |
| Hybrid Engine Phase 0 | ✅ architecture, No-SQL mandate, and deployment profiles |
| Bilingual documentation | 🚧 processed in thematic iterations |
| It.68 implementation | ⏸️ waiting for the docs gate |
| It.69–77 | ⏳ planned |
| It.58d, It.67, It.25 | ⏳ parallel / pre-Final backlog |

**Important:** “latest” in this document means the latest release recorded in the August 2, 2026 source bundle.

---

## 3. Non-negotiable rules

Development must preserve these invariants:

1. **Files are the SSOT.** Redis, cache, the index, a Git remote, or S3 are not the authority for primary content.
2. **Classic mode works without optional services.** A new driver has a local fallback or a clearly defined safe failure.
3. **Writes go through domain services.** No endpoint or plugin may bypass validation, ACL, audit, and safe writing.
4. **Admin auth remains session + CSRF.** Headless auth in It.74 is additive.
5. **Untrusted code fails closed.** Imports, Monaco, plugins, themes, and generated code pass a policy/schema gate.
6. **Documentation and code share the same state.** Planned features are `⏳`; partial features are `🟡`; shipped only after the end-to-end gate.
7. **SK and EN change together.** Class names, endpoints, paths, and configuration keys remain identical.

---

## 4. Shipped foundations

### Content core

- file-backed pages and articles CRUD,
- indexing and pagination,
- locks, heartbeat, auto-save, and revisions,
- OCC and HTTP 409 conflicts,
- three-way merge and conflict resolver,
- scheduled publishing through the scheduler,
- SEO metadata, tags, filters, and the public blog.

### Platform

- session authentication, 2FA, RBAC, and ACL,
- settings with encrypted secret fields,
- audit, logs, WAF, rate limiting, and security middleware,
- job scheduler, worker, backups, trash, and diagnostics,
- external plugins, hooks, Code Policy, and Developer Mode,
- Docker/release/deployment foundation.

### Frontend and public site

- React SPA with admin and public routes,
- SK/EN i18n,
- Markdown and Tiptap editor profiles,
- DAM, navigation, comments, contact, newsletter, and gallery,
- system update UI and demo mode.

Inventory: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 5. Next implementation target — It.68

It.68 is a foundation iteration, not a visual feature. Before the first commit, confirm:

- the boundary between `StorageInterface` and existing repository services,
- compatibility with current paths and files,
- a safe local driver as the default,
- the schema registry and behavior for invalid legacy documents,
- engine settings and their Classic defaults,
- index rebuild/diagnose behavior,
- zero requirement for an SQL migration.

### Expected It.68 output

1. contracts and local driver,
2. integration tests reading and writing existing content,
3. schema registry for documents written by the admin,
4. settings schema + admin display without activating unfinished drivers,
5. in-place migration or a compatibility adapter,
6. updated architecture, API, testing, and user documentation.

---

## 6. Order after It.68

```text
It.68 storage/schema/settings
  └─► It.69 cache + Redis + HTTP validators
        ├─► It.70 Git publish
        ├─► It.71 Performance Guard
        └─► It.72 media drivers
              └─► It.73 locale document
                    ├─► It.76 LibreTranslate
                    ├─► It.77 cloud translation
                    └─► It.75 AI agent

It.74 API keys/JWT may begin after the It.68 auth/storage contract is stable.
```

It.58d and It.67 may proceed in parallel, but they must not alter the same abstractions without coordination.

---

## 7. Iteration workflow

1. Read `.cursorrules`, the roadmap, the relevant specification, and related incidents.
2. Verify the current tag/commit and the actual test commands.
3. Record scope, non-goals, and dependencies.
4. Design the contract and security boundaries first.
5. Implement the backend with tests.
6. Add the typed FE client and UI, or a headless example.
7. Run the full gate.
8. Perform a manual smoke test.
9. Update SK/EN documentation, API docs, and changelog.
10. Only then create the tag/release.

### Quality gate

```bash
composer gate
# or explicitly:
composer test
composer stan
composer cs

cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test
```

The actual scripts in `composer.json` and `package.json` take precedence over this documentation example.

---

## 8. Checks before changing storage

For every storage/cache/publish change, answer:

- Which file is authoritative?
- Is the write atomic?
- What happens if the index fails after the write?
- What happens if Redis/Git/S3 is unavailable?
- Can the derived layer be completely deleted and rebuilt?
- Does the operation pass the same ACL, sanitization, and audit?
- Can the installation return to Classic mode without data migration?

If the answers are unclear, implementation is not ready.

---

## 9. Active open decisions

| Topic | Decision needed |
|-------|-----------------|
| License | align the stated open-source philosophy and desired commercial restrictions with the actual `LICENSE` |
| Final 1.0 scope | decide which Hybrid Engine iterations block GA and which may remain post-1.0 |
| It.48 vs It.70 | one publishing pipeline, not two competing implementations |
| It.58d | precisely define the remainder after shipped 58b/58c |
| It.67 | define the mandatory gate for theme imports, shortcodes, and generated code |
| Community beta | obtain external smoke tests, not maintainer-only QA |

---

## 10. Avoid

- restoring priorities from archived sections of the old `CONTINUATION.md`,
- creating a new “Iteration 30/31…” when that number already has a different history,
- presenting the existing GitHub content sync as a complete Git publish engine,
- presenting file cache as the finished Redis layer,
- storing API keys or cloud secrets in plaintext,
- writing content files directly from React, a plugin, or the AI agent,
- shipping a release without updating both language editions.

---

## 11. Ready-to-paste continuation brief

```text
We are continuing development of PaginiumCMS.

Documentation checkpoint: 2026-08-02
Latest release recorded in the docs: v2.1.0-beta.23
Direction: Hybrid Headless Content Engine with a mandatory No-SQL file SSOT.
Stack: React + TypeScript + Vite ↔ Slim REST API ↔ PHP 8.5 Core.

Current state:
- Phase 0 architecture is documented.
- Bilingual SK/EN documentation consolidation is in progress.
- It.68+ implementation is paused until the docs gate is complete.

Next code:
- It.68: StorageInterface, local driver, schema registry, engine settings,
  compatibility with existing files, and rebuild diagnostics.

Mandatory laws:
- files are the only source of truth,
- Classic mode must work without Redis/Git/S3,
- admin session + CSRF remains unchanged,
- every write passes validation, ACL, audit, and the safe writer,
- SK and EN documentation is updated together.

Before implementation, verify the current git tag, CHANGELOG, the relevant ITERATION doc,
.cursorrules, and actual test scripts. Run the full gate and a smoke test after changes.
```

---

*Keep this file short and current. Old handoff blocks belong in release history, not in the active briefing.*
