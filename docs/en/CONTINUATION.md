# PaginiumCMS — development continuation context

> **Purpose:** concise, current handoff for the next development session  
> **Checkpoint:** August 2, 2026 · `v2.1.0-beta.23`  
> **Active decision:** It.69 (cache layer) is the next Hybrid Engine code target after It.68

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
| Bilingual documentation | ✅ consolidated (It.18) |
| It.68 implementation | ✅ Hybrid Engine foundation shipped |
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

## 5. Active phase — stabilization (August 2026)

**Do not start new iteration scope.** See [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md).

Focus: onboarding (S1–S4), content path (S5–S9), layout basic (S10–S14), platform (S15–S20), Classic profile (S21–S24), quality gate (S25–S28). **Active feature slice: [It.25](ITERATION_25.md)** (stable-release blocker). Target: first stable tag **September 2026** when §5.1–5.2 in [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) are met.

Historical note: It.69+ were the Hybrid Engine build targets before stabilization; foundations shipped in beta.26–beta.30 — remainder is profile documentation and polish, not new drivers during freeze.

---

## 6. Historical Hybrid order (pre-stabilization)

```text
It.68 storage/schema/settings ✅
  └─► It.69 cache + HTTP validators ✅
        ├─► It.70 Git publish ✅ foundation
        ├─► It.71 Performance Guard ✅
        └─► It.72 media drivers 🟡 local MVP
              └─► It.73 locale ✅
                    ├─► It.76/77 translation ⏸️
                    └─► It.75 AI agent ⏸️
```

Resume new work only after [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) §5 exit criteria.

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
- It.68 Hybrid Engine foundation is shipped in [Unreleased].
- Bilingual SK/EN documentation consolidation is in progress (EN first for It.68).

Next code:
- It.69: unified cache layer, optional Redis driver, HTTP validators,
  safe Classic fallback, and cache diagnostics.

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
