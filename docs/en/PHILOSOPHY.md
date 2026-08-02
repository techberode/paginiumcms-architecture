# PaginiumCMS — philosophy and reason for existence

> The project's guiding idea. It applies to every iteration, deployment mode, and future development decision.

---

## Why PaginiumCMS exists

PaginiumCMS began as a **small key to the large world of web application development**.

Its purpose is not to create another opaque “CMS package,” but to show the **entire path** — from file storage through a REST API to a React administration interface — in a form that people can **learn from, experiment with, and understand**, rather than merely click through without knowing what happens underneath.

The project is also a practical **development laboratory**. Each iteration introduces real topics such as authentication, RBAC, indexing, caching, feeds, SEO, WAF, blueprints, job scheduling, and security controls. Everything remains in one coherent repository that can be read, tested, forked, and adapted.

Since August 2026, PaginiumCMS is no longer described only as a “flat-file CMS.” Its target is a **Hybrid Headless Content Engine**: an API-first system that retains files as the source of truth while adding professional layers for performance, distribution, collaboration, and automation.

---

## Immutable principles

| Principle | Meaning |
|-----------|---------|
| **No-SQL is mandatory** | Neither SQL nor an external document database may become the primary source of truth for CMS content, users, configuration, or operational state. Data remains in JSON, Markdown, and YAML files. Redis, APCu, and memory layers are derived cache or temporary coordination only. See [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md). |
| **Files are the source of truth** | Content and configuration must remain portable, readable, and recoverable without a mandatory external database service. |
| **Open source code** | The Core, modules, and system mechanisms should remain publicly readable, auditable, and modifiable within the terms of the repository license. |
| **The official project remains free** | PaginiumCMS must not evolve toward a closed Core, a paid “Pro” edition, or a paywall around essential features. Legal terms for use, redistribution, and any commercial use are always defined by the `LICENSE` file. |
| **API First** | Every important administration operation should have a defined API contract and must not exist only as a UI action. |
| **Security by Design** | Authentication, authorization, validation, sensitive-data encryption, and auditability belong in the design itself, not in a later patch list. |
| **Thin Core and modules** | The Core provides stable interfaces and security rules. Features should grow through modules, drivers, hooks, and explicit contracts. |
| **Documentation is part of the product** | Decisions, limitations, implementation status, and migration steps must remain discoverable in the repository. |
| **Collaboration and history** | Project direction is formed by human decisions, implementation, tests, and feedback. Important decisions must not exist only in chat logs or in one maintainer's memory. |

These principles are not marketing copy. They form the project's **decision framework**. A new feature may change an implementation, but it must not silently change the source of truth, the security model, or the open nature of the Core.

> **License note:** this philosophy describes the intention of the official project. Legal rights and restrictions are defined only by the current repository `LICENSE` file. Documentation must not claim a legally enforceable restriction that the license itself does not contain.

---

## What PaginiumCMS is not

- ❌ A mandatory cloud service without which content stops working
- ❌ A SQL application merely presented as a flat-file CMS
- ❌ Closed hosting where users cannot inspect the code or access their own data
- ❌ A black box with an administration UI but no explainable API or data model
- ❌ A product with a closed Core and paid unlocking of essential features
- ❌ A simplistic “read one file for every visitor” blog without indexing, caching, locks, or concurrency control

---

## The demo subdomain is a showcase environment

`demo.paginiumcms.com` is a **training and demonstration instance**:

- it exists to let visitors explore CMS capabilities,
- it is not a production store for user data,
- it may be reset regularly to a clean state,
- it uses the same open-source code with a different deployment purpose,
- security restrictions of `DEMO_MODE` must not be copied blindly into production.

See [ITERATION_13.md](ITERATION_13.md) for details.

---

## New technical direction — Hybrid Headless Content Engine

PaginiumCMS **does not abandon the No-SQL principle**. It extends its original flat-file foundation into a layered content engine:

1. **Flat-File Core** — physical JSON, Markdown, and YAML files remain the only source of truth.
2. **Storage abstraction** — a stable interface separates domain logic from concrete read/write mechanics.
3. **Index** — aggregated metadata accelerates lists, search, and filtering without scanning every file.
4. **Cache** — file cache, APCu, or Redis accelerate reads; every cache must be disposable and rebuildable.
5. **API layer** — the REST API handles authentication, permissions, validation, conflicts, and consistent responses.
6. **Git distribution** — content may be published immediately or in batches through commit/push workflows.
7. **Observability** — latency, memory, I/O, and error measurements protect engine performance.
8. **Multilingual and AI-assisted workflows** — locales and assisted translation are built on the file document model, not on a mandatory database.

| Document | Purpose |
|----------|---------|
| [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) | Target engine architecture and layers |
| [architecture/DEPLOYMENT_MODES.md](architecture/DEPLOYMENT_MODES.md) | Classic, Hybrid, and Git-headless modes |
| [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) | Immutable file source-of-truth rule |
| [ITERATION_WAVE_HYBRID_ENGINE.md](ITERATION_WAVE_HYBRID_ENGINE.md) | Implementation wave It.68–77 |
| [ROADMAP.md](ROADMAP.md) | Overall development map |

**Implementation of the new direction continues only after the bilingual documentation pass is complete and reviewed**, so the Slovak and English editions cannot create two different interpretations of the same product.

---

## Historical principles that remain valid

- **Flat-file first** — data remains visible and portable as files.
- **Modularity** — the Core stays narrow and features evolve in modules.
- **Iterative development** — every major feature has its own design, implementation, tests, and documentation.
- **Docs first** — architecture and decisions are documented before broad code changes.
- **Testability** — automated tests and static analysis are part of the definition of done.

---

## Who the project is for

- **Developers** who want to understand a modern PHP and React full stack.
- **Administrators and self-hosters** who want to own both content and operational data.
- **Content creators** who need a capable administration interface without giving the source of truth to a third-party platform.
- A **community** that can audit, test, and extend the project through documented interfaces.

---

## Related documents

- [README.md](README.md) — current status and documentation entry point
- [ROADMAP.md](ROADMAP.md) — iterations and development direction
- [CONTINUATION.md](CONTINUATION.md) — project continuation context
- [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) — target Hybrid Engine
- [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) — No-SQL rule
- [ITERATION_13.md](ITERATION_13.md) — demo sandbox

---

*This page is the reference point for architectural decisions. A proposal that silently introduces a mandatory database, closes the Core, or bypasses security contracts does not belong in the main PaginiumCMS line.*
