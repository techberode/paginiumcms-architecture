# PaginiumCMS — project roadmap

> **Documentation checkpoint:** August 6, 2026  
> **Latest release:** see [`CHANGELOG.md`](../../CHANGELOG.md) (currently `v2.1.0-beta.67`) · handoff: [CONTINUATION.md](CONTINUATION.md)  
> **Direction:** Hybrid Headless Content Engine · No-SQL file source of truth · API-first  
> **Code status:** Stabilization phase — It.25 M1+ shipped; next target `v2.2.0` stable

This roadmap is the canonical map of the **future direction**. Release history belongs in [`CHANGELOG.md`](../../CHANGELOG.md), detailed implementation specifications in `ITERATION_*.md`, and incidents in [`ISSUES.md`](ISSUES.md).

**Architecture:** [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) · **No-SQL mandate:** [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) · **Backlog:** [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)

| Symbol | Meaning |
|--------|---------|
| ✅ | shipped and wired end to end |
| 🟡 | partially shipped or a clearly bounded remainder exists |
| ⏳ | planned; implementation has not started |
| ⏸️ | intentionally paused |
| 🔴 | critical architecture or security priority |

---

## 1. Strategic goal

PaginiumCMS is evolving from a production-capable flat-file CMS into a **Hybrid Headless Content Engine**. The pivot does not change the core data principle:

1. JSON, Markdown, and YAML files remain the primary source of truth.
2. The index, cache, Redis, Git, and external media storage are derived or distribution layers.
3. Classic mode remains fully supported and is the safe fallback without Redis, S3, or a Git service.
4. Admin sessions, CSRF, RBAC, and 2FA remain the primary administration model.
5. API keys and JWT are added for headless clients; they must not bypass existing authorization.
6. Every new layer must provide diagnostics, fallback behavior, and recovery from the file source of truth.

---

## 2. Current milestone — Documentation Phase 0

| Area | Status | Result |
|------|--------|--------|
| Identity and philosophy | ✅ | the project is consistently described as a Hybrid Headless Content Engine |
| No-SQL mandate | ✅ | primary and derived state are clearly separated |
| Deployment modes | ✅ | Classic, Hybrid, and Git-headless profiles |
| Roadmap and backlog | ✅ this iteration | stale priorities and duplicate iteration numbers removed |
| Bilingual documentation | 🚧 | separate, structurally matching `SK/` and `EN/` trees |
| Hybrid Engine foundation (It.68) | ✅ | `[Unreleased]` — storage abstraction, schema registry, engine settings |
| Hybrid Engine layers It.69–77 | ⏳ | cache, Git publish, APM, media drivers, locale, API auth, AI/translation |

The **documentation gate is complete** when the SK and EN editions do not contradict each other, feature states match, and planned capabilities are not presented as shipped.

---

## 3. Shipped Public Beta foundation

Instead of repeating dozens of historical specifications, this roadmap groups shipped work by capability.

| Area | Status | Main capabilities |
|------|--------|-------------------|
| Content and collaboration | ✅ | pages, articles, locks, auto-save, revisions, three-way merge, conflicts, scheduled publishing |
| Editors | ✅ | Markdown, modular Tiptap/WYSIWYG, JSON storage, media upload, custom editor components |
| Public website | ✅ | React site, blog, SK/EN i18n, SEO, feeds, navigation, feature gallery, newsletter |
| Administration | ✅ | dashboard, search, filters, bulk actions, trash, backups, scheduler, system update |
| Security | ✅ ongoing | sessions, CSRF, RBAC, 2FA, encryption, WAF, rate limits, SSRF/Zip-Slip/path controls, audit |
| Extensions | ✅ foundation | external plugins, hooks, Code Policy, Developer Mode, Code Editor |
| Operations | ✅ foundation | Docker onboarding, health, monitoring, logs, release and deployment workflow |
| Hybrid Engine layers | 🟡 | It.68 foundation shipped; unified cache/Redis/Git/APM/S3 remain planned |

Detailed inventory: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 4. Active Hybrid Engine wave

| It. | Topic | Priority | Status | Dependencies / note |
|-----|-------|----------|--------|---------------------|
| **68** | Storage abstraction, schema registry, and engine settings | 🔴 | ✅ `[Unreleased]` | local driver; settings + JSON content write slice |
| **69** | Unified cache, Redis, `ETag`, `Last-Modified` | 🔴 | ✅ | absorbs legacy It.45 and It.49; Redis driver deferred |
| **70** | Git publish — immediate and queued | 🟡 | ⏳ | uses scheduler/queue |
| **71** | Performance Guard APM | 🟡 | ⏳ | latency, I/O, memory, and incident measurement |
| **72** | Flysystem media drivers, S3/CDN | 🟡 | ⏳ | local driver remains the default |
| **73** | Multiple locales in one content document | 🟡 | ⏳ | prerequisite for assisted translation |
| **74** | Additive API keys and JWT | 🟡 | ⏳ | admin session + CSRF remain unchanged |
| **75** | CMS-aware AI agent | 🔵 | ⏳ | human-approved proposals; no autonomous publishing |
| **76** | Self-hosted assisted translation | 🔵 | ⏳ | LibreTranslate / compatible driver |
| **77** | Cloud assisted translation | 🔵 | ⏳ | DeepL, Google, and additional drivers |

Dependency map: [ITERATION_WAVE_HYBRID_ENGINE.md](ITERATION_WAVE_HYBRID_ENGINE.md).

---

## 5. Recommended implementation order

### HE-1 — safe storage abstraction

**It.68** shipped the foundation layer:

- ✅ `StorageInterface` and a compatible local driver,
- ✅ JSON Schema registry (`settings.overrides@1`) for admin documents,
- ✅ `engine.*` settings with safe Classic defaults,
- ✅ regression tests against existing files,
- ⏳ full repository migration and rebuild diagnostics in follow-ups.

**It.69** must follow before additional read-performance layers:

**It.69** unifies caching and conditional HTTP responses:

- memory/file/Redis drivers,
- safe fallback when Redis is unavailable,
- invalidation after a successful write,
- `ETag` and `Last-Modified`,
- a test proving that deleting all cache state does not lose content.

### HE-3 — distribution and static output

**It.70** introduces the Git publishing workflow. **It.48** may add static/Jamstack output in parallel, but the source document must be safely written locally first.

### HE-4 — observability

**It.71** and the remaining It.46 scope add application and host metrics. Performance Guard must not alter content; it may only measure, alert, and execute documented safe fallbacks.

### HE-5 — media and headless access

**It.72** adds media drivers. **It.74** adds scope-limited API keys/JWT. Both layers use the same validators, ACL, and audit as the local/session flow.

### HE-6 — localized content and assisted workflows

The order is **It.73 → It.76/77 → It.75**. The AI agent may only propose changes; writes and publishing require explicit confirmation by an authorized user.

---

## 6. Parallel tracks

| Track | Status | Timing |
|-------|--------|--------|
| **It.58d** — remaining layout blocks/polish | ⏳ | after docs; may run alongside early Hybrid Engine work |
| **It.67** — untrusted surfaces defense-in-depth | 🔴 | before expanding imports, themes, and generated code |
| **It.25** — setup wizard and simplified update UX | ✅ M1+ shipped | `beta.62`–`beta.65` — preflight + update banner; before stable tag |
| **It.48** — static/dynamic rendering | 🟡 | align with It.70 to avoid two publishing pipelines |
| Community beta testing | 🔴 | ongoing before 1.0 |
| Documentation and security review | 🔴 | with every shipped wave |

---

## 7. Path to Final 1.0

```text
It.68 foundation (shipped)
    → It.69 cache + HTTP validators
    → first Hybrid Engine stabilization releases
    → It.67 security gate
    → community beta and fixes
    → It.25 onboarding/update UX
    → final documentation + SECURITY_REVIEW
    → 1.0.0 GA
```

Final 1.0 **does not require every It.68–77 capability to ship**. GA scope must be frozen by a separate release decision. Minimum gate:

- no open critical security defects,
- reproducible clean installation,
- working backup/restore and diagnostics,
- documented cron and update procedures,
- verified Classic fallback,
- beta smoke tests performed outside the maintainer's development environment.

---

## 8. Architecture laws

1. **No-SQL SSOT:** neither SQL nor a document database may become the authority for content or configuration.
2. **External code outside Core:** plugins and user code must not grow unchecked inside `Core/`.
3. **API ↔ FE parity:** a new admin endpoint has a typed client, permissions, and UI, or an explicit reason for being headless-only.
4. **Fail closed on writes:** invalid schema, path, permission, or code blocks the mutation.
5. **Derived layers are rebuildable:** the index and cache may be discarded and reconstructed.
6. **Classic fallback:** an optional service must not become a hidden requirement for the base CMS.
7. **Documentation is part of the release:** roadmap, feature overview, API, and changelog are updated with the feature.

---

## 9. Definition of Done

Every implementation iteration must include:

1. approved scope and explicit non-goals,
2. security and No-SQL design review,
3. backend contract, validation, and authorization,
4. typed frontend or a documented headless contract,
5. PHPUnit, PHPStan L8, TypeScript, ESLint, and Vitest gate,
6. migration/fallback scenario,
7. updated SK and EN documentation,
8. changelog and release notes,
9. manual smoke test of the critical path.

---

## 10. Roadmap maintenance rules

- The roadmap contains future direction and major milestones, not a detailed incident log.
- Shipped status is verified against `CHANGELOG.md` and code, not an old planning table.
- An iteration number must never be reused for another feature.
- An absorbed proposal is marked “absorbed by”, not kept as a separate active iteration.
- “Latest version” means **the latest release recorded in the documentation snapshot** until a newer release record is confirmed.
