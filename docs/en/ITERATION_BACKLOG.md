# PaginiumCMS — consolidated backlog

> **Snapshot:** `v2.1.0-beta.32` · August 9, 2026  
> **Rule:** the active backlog contains only unshipped or precisely bounded remaining scope  
> **No-SQL:** [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md)

This document fixes the old backlog, which mixed shipped iterations, planned features, absorbed proposals, and reused numbers. Release history belongs in `CHANGELOG.md`; details of shipped capabilities belong in [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

| Symbol | Meaning |
|--------|---------|
| 🔴 | critical priority |
| 🟡 | medium priority |
| 🔵 | lower / optional priority |
| ⏳ | actively planned |
| ⏸️ | paused by decision |
| 🟡 partial | foundation shipped, explicit remainder exists |
| ↪ | absorbed by another iteration |
| ✅ | shipped; shown only in the consolidation table, not as active backlog |

---

## 1. Active priorities

| Order | Item | Priority | Status | Reason |
|-------|------|----------|--------|--------|
| 1 | Complete bilingual documentation | 🔴 | ✅ | It.18 consolidation shipped; SK detail catch-up deferred |
| 2 | **It.68** Hybrid Engine foundation | 🔴 | ✅ | shipped in `v2.1.0-beta.28` (It.68 bundle) — see [ITERATION_68](ITERATION_68.md) |
| 3 | **It.69** Unified cache + Redis + HTTP validators | 🔴 | ✅ | shipped in `v2.1.0-beta.26` — see [ITERATION_69](ITERATION_69.md) |
| 4 | **It.67** Untrusted surfaces hardening | 🔴 | ✅ | shortcodes, themes, CSP, hostile fixtures — see [ITERATION_67](ITERATION_67.md) |
| 5 | **It.70** Git publish modes | 🟡 | ✅ | local publisher + queued/immediate API — see [ITERATION_70](ITERATION_70.md) |
| 6 | **It.71** Performance Guard | 🟡 | ✅ | shipped in `v2.1.0-beta.28` — see [ITERATION_71](ITERATION_71.md) |
| 7 | **It.72** Media drivers | 🟡 | 🟡 partial | MVP local driver + probe shipped; S3/migration deferred |
| 8 | **It.73** Multi-locale document | 🟡 | ⏳ | translation foundation |
| 9 | **It.74** API keys/JWT | 🟡 | ✅ | shipped in `v2.1.0-beta.30` — see [ITERATION_74](ITERATION_74.md) |
| 10 | **It.80** SEO, integrations & ops toolkit | 🟡 | ⏳ | redirects, 404 report, webhooks, GDPR, CLI — see [ITERATION_80](ITERATION_80.md) |
| 11 | **It.58d** Layout remainder | 🟡 | ⏳ | precisely freeze remaining blocks |
| 12 | **It.25** Setup wizard/update UX | 🟡 pre-Final | ⏳ | onboarding and GA polish |
| 13 | **It.76/77** Translation providers | 🔵 | ⏳ | after It.73 |
| 14 | **It.75** AI agent | 🔵 | ⏳ | after locale and provider layers |
| 15 | **It.78** Unified upload security | 🟡 | ⏳ | security gate before video / new MIME types |
| 16 | **It.79** DAM video | 🟡 | ⏳ | self-hosted MP4/WebM + editor embed; after It.78 |

---

## 2. Hybrid Engine backlog It.68–77

### It.68 — foundation 🔴 ✅ shipped (`[Unreleased]`)

- `StorageInterface` and compatible local driver,
- engine settings with Classic default,
- JSON Schema registry (`settings.overrides@1`),
- settings read path + JSON content write slice,
- capability probe in admin settings,
- diagnostics, migration notes, and regression tests.

See [ITERATION_68.md](ITERATION_68.md) and [ISS-121–123](../ISSUES.md#iss-121).

### It.69 — cache and HTTP validators 🔴 ✅ shipped (`[Unreleased]`)

- memory/file/auto driver factory with tag invalidation,
- read-through content cache and deterministic invalidation,
- Redis fallback (`redis` → `auto`; driver deferred),
- `ETag`, `Last-Modified`, and `304` on public GET slice,
- cache health/diagnose and operations runbook,
- absorbs It.45 and It.49 proposals.

See [ITERATION_69](ITERATION_69.md).

### It.70 — Git publishing ✅ (foundation)

- local `immediate` and `queued` strategies via `GitPublishService`,
- flat-file publish queue and `git.publish` scheduler handler,
- admin API + `git:publish` permission; SSOT write hook without rollback on Git failure,
- `github_api` publisher and full publish UI modal → deferred follow-up.

See [ITERATION_70](ITERATION_70.md).

### It.71 — Performance Guard ✅ shipped (`v2.1.0-beta.28`)

- middleware timing and memory/I/O metrics,
- budgets per route/workflow,
- incidents and reports,
- documented self-heal only for derived layers,
- no automatic primary-content changes.

### It.72 — media drivers 🟡 partial (MVP shipped)

- ✅ `MediaStorageDriverInterface`, local driver, factory, capability probe (Iteration 72 MVP),
- ✅ settings `media.storageDriver` + reserved S3 fields,
- ⏳ S3-compatible driver,
- ⏳ signed/public URL policy,
- ⏳ reference migration without content loss,
- ⏳ MIME/path/security parity for remote driver.

See [ITERATION_72](ITERATION_72.md).

### It.78 — unified upload security 🟡

- `UploadPolicyEngine` and named profiles for every upload surface,
- intersection semantics for MIME/size across settings groups,
- shared magic-byte, filename, Zip-Slip, and SSRF guards,
- upload audit without content/secrets in logs,
- blocks ad-hoc upload paths before It.79 video.

See [ITERATION_78](ITERATION_78.md).

### It.79 — DAM video 🟡

- MP4/WebM via `media-video` profile (It.78),
- Media Library filter/preview + editor embed (Markdown + Tiptap),
- sanitizer allow-list for self-hosted `<video>` only; no iframe embeds,
- separate `media.maxVideoUploadSizeKb`.

See [ITERATION_79](ITERATION_79.md).

### It.73 — multi-locale content 🟡

- one document with locale variants,
- fallback locale,
- per-locale validation and SEO,
- editor tabs/diff,
- compatibility with legacy single-locale documents.

### It.74 — API keys and JWT 🟡 ✅ shipped (`v2.1.0-beta.30`)

- scope-limited keys,
- hash at rest, plaintext only at creation,
- revoke/rotate/audit/rate limit,
- JWT expiry and audience,
- no regression to admin session + CSRF.

See [ITERATION_74](ITERATION_74.md).

### It.80 — SEO, integrations & operator toolkit 🟡 ⏳

Checklist-driven product wave (sub-phases `80a`–`80g`):

| Sub | Feature | Priority |
|-----|---------|----------|
| 80a | Redirect manager (301/302) | P1 |
| 80b | 404 tracking report | P2 |
| 80c | Comment spam heuristics | P3 |
| 80d | Outbound webhooks | P4 |
| 80e | GDPR export/anonymize | P5 |
| 80f | CLI toolkit | P6 |
| 80g | CMS import (WP/Jekyll/Ghost) | P7 |

See [ITERATION_80](ITERATION_80.md). May ship across `beta.31+` slices; **80a** recommended first.

### It.75 — CMS-aware AI agent 🔵

- tool allow-list,
- agent runs as the signed-in user,
- patch proposals without automatic saving,
- async queue,
- provider adapter and outbound guard,
- audit without sensitive content payload logging.

### It.76 — self-hosted translation 🔵

- shared `TranslationProviderInterface`,
- LibreTranslate-compatible driver,
- preview/diff and explicit Apply,
- quota/rate limit,
- SSRF protection and timeout.

### It.77 — cloud translation 🔵

- DeepL/Google drivers,
- encrypted credentials,
- usage meter and generic errors,
- optional fallback,
- no live network in CI.

---

## 3. Pre-Final backlog

### It.25 — setup wizard and simplified update UX 🟡

Shipped foundation:

- `first-run.sh`,
- admin bootstrap,
- system update engine/panel,
- installation and first steps documentation.

Remaining:

- `/setup` only for an uninstalled state,
- admin/site profile step,
- safe completion with an installed flag,
- dashboard update banner and simple “Update now,”
- rollback/backup for update UX,
- clean install and supported beta upgrade tests.

### Community beta testing 🔴

- clean install on third-party infrastructure,
- upgrade and rollback,
- non-maintainer UX feedback,
- security review,
- documentation reproducibility.

### Final release gate 🔴

- frozen GA scope,
- no open critical incidents,
- release candidate,
- final SK/EN documentation,
- backup restoration drill,
- release notes and support policy.

---

## 4. Parallel product backlog

| Item | Status | Note |
|------|--------|------|
| **It.58d** layout blocks/polish | ⏳ | must not create a second page model |
| **It.48** static/dynamic rendering | ⏳ | combine design with It.70 publishing pipeline |
| Remaining theme runtime | 🟡 | depends on It.67 and schema/policy gate |
| Server metrics agent (remaining It.46) | ⏳ | coordinate with It.71 |
| **It.79** DAM video | ⏳ | It.78 + It.72 MVP |
| Scoped FileManager | ⏳ candidate | assign a new unique number after scope approval |
| Frontend inline editing | ⏳ candidate | reuse existing lock/editor flow |
| Finer comment moderation/CAPTCHA | ↪ **It.80c** | honeypot + heuristics in [ITERATION_80](ITERATION_80.md) |
| Redirect manager / 404 ops | ↪ **It.80a/b** | [ITERATION_80](ITERATION_80.md) |
| Outbound content webhooks | ↪ **It.80d** | [ITERATION_80](ITERATION_80.md) |
| GDPR export / CLI / CMS import | ↪ **It.80e/f/g** | [ITERATION_80](ITERATION_80.md) |
| Contextual Actions | ⏳ candidate | the old “It.30” label must not be reused |
| System overview polish | ⏳ candidate | may be part of It.71/ops dashboard |

---

## 5. Absorbed proposals

| Old proposal | New canonical target | Status |
|--------------|----------------------|--------|
| It.45 Redis infrastructure | **It.69** | ↪ absorbed |
| It.49 Unified cache | **It.69** | ↪ absorbed |
| It.31 Live Preview | It.51 + It.58d | 🟡 foundation shipped; define remainder |
| It.36 Pagination | It.19 + It.44 | ✅ shipped |
| It.39 comments/guest foundation | comments policy + workflow | ✅ foundation; only concrete extensions remain |
| It.46 metrics | It.71 + host metrics sub-scope | ↪ coordinate |
| It.48 publishing idea | It.70 + static renderer | ↪ shared design |

---

## 6. Corrected stale states

The old backlog incorrectly marked these items as planned or incomplete:

| It. | Feature | Correct status |
|-----|---------|----------------|
| 43 | Advanced search / command palette | ✅ shipped |
| 44 | Filters and public blog pagination | ✅ shipped |
| 47 | Notification connector auth | ✅ foundation shipped |
| 50 | In-App Micro Firewall | ✅ `2.0.26` |
| 53 | Smooth SPA reload | ✅ `2.0.39` |
| 54 | Modular editor profiles | ✅ `2.0.42` |
| 55 | Tiptap JSON + upload | ✅ `2.0.43` |
| 56 | Rich navigation | ✅ `beta.5` |
| 57 | Auto tags/meta | ✅ `beta.4` |
| 59 | Scheduled publishing | ✅ `2.0.53` |
| 60 | Custom editor components | ✅ foundation |
| 61 | Newsletter v2/footer | ✅ `beta.16`–`beta.18` |
| 62 | Scheduler hardening | ✅ `beta.9` |
| 63 | System update | ✅ `beta.12`–`beta.15` |
| 65 | Feature gallery | ✅ phases 1–3 |
| 66 | Security write-time packs | ✅ `beta.22` |
| 58b/58c | Appearance + Layout Switch | ✅ `beta.8` / `beta.23` |
| 68 | Hybrid Engine foundation | ✅ `[Unreleased]` |

Shipped items continue through incidents and follow-up specs, not as entire active iterations.

---

## 7. Numbering and backlog hygiene

1. An iteration number is globally unique.
2. An old idea without an approved specification receives a temporary `candidate:<slug>` name, not a recycled number.
3. After shipping, the iteration moves to release history; only an explicit remainder stays in the backlog.
4. Absorbed scope has one owner and one acceptance set.
5. Status is determined from changelog, code, tests, and end-to-end wiring, not from an old document heading.
6. “Partial” must state exactly what is shipped and what remains.
7. Every new item includes dependencies, non-goals, security gate, No-SQL impact, and Definition of Done.

---

## 8. Recommended order

```text
Docs gate
  → It.69
  → It.67 security hardening
  → It.70 / It.48 unified publishing design
  → It.71 + remaining It.46
  → It.72 (MVP done; S3/migration remainder)
  → It.73
  → It.74 ✅
  → It.78 (upload security gate)
  → It.79 (DAM video)
  → It.76 / It.77
  → It.75

Parallel where safe: It.80 (80a→80g by impact/effort), It.58d, beta fixes, community testing.
Pre-Final: It.25 + GA gate.
```

Change this order only together with roadmap, dependency, and both language-edition updates.
