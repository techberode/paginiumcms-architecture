# Iteration 73 — multi-locale content document

> **Status:** ✅ Complete in `[Unreleased]` (Phases 1–2e) · beta tag pending  
> **Priority:** 🟡 · high migration impact  
> **Wave:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.68](ITERATION_68.md)

## Goal

Support several locale variants of one content resource without creating unrelated copies and a second workflow. It.73 changes the **content model**, not admin UI translations.

Legacy single-locale documents remain readable. Migration must be optional, backed up, and reversible.

---

## Canonical model

Draft JSON document:

```json
{
  "schemaVersion": 2,
  "slug": "about-us",
  "type": "page",
  "defaultLocale": "sk",
  "localizedContent": {
    "sk": {
      "title": "O nás",
      "body": "…",
      "seo": { "title": "O nás", "description": "…" }
    },
    "en": {
      "title": "About us",
      "body": "…",
      "seo": { "title": "About us", "description": "…" }
    }
  },
  "localeStatus": { "sk": "published", "en": "draft" },
  "revision": 3,
  "updatedAt": "2026-08-02T10:00:00+00:00"
}
```

Exact names are locked in API/schema documentation before code. One resource has one identity, global metadata, and locale-scoped content/status/SEO where the domain requires it.

### Markdown strategy

Each content type must select **one canonical format**:

- a bundled JSON document, or
- primary Markdown plus a versioned `.i18n.json` sidecar with unambiguous ownership rules.

The system must not maintain two authoritative bodies for the same locale. A sidecar contract states exactly which fields belong to Markdown and which to JSON.

---

## Locale resolution

The public API uses deterministic order:

1. explicit `?locale=` or route locale,
2. supported `Accept-Language` when that endpoint allows negotiation,
3. resource/site `defaultLocale`,
4. first available locale on the resource when `content.localeFallbackEnabled` is `true` (default),
5. otherwise `404` or a clear no-translation state.

The response identifies the resolved locale and whether fallback occurred. It.69 cache keys and `Vary` include locale.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `LocalizedContentNormalizer` | legacy → canonical read model without immediate mutation |
| `LocalizedContentValidator` | schema and publish rules per locale |
| `ContentRepository` | atomic read/write of the entire resource and revision check |
| `ContentIndexService` | `locales`, publish status, and locale facets |
| `LocaleResolver` | shared fallback policy for API and rendering |
| migration CLI | inventory, dry-run, backup, convert, verify, rollback |
| events | locale-aware content saved/published/translated events |

A single `revision` protects the whole document in the MVP. If real translator conflicts justify per-locale revisions, that becomes a separate OCC/API decision rather than an undocumented addition.

---

## Validation and publishing

- an empty draft locale is allowed,
- a locale marked `published` must satisfy required fields and SEO policy,
- publishing one locale does not have to publish the others,
- slug/identity is global; locale-specific slugs are outside the MVP or require a separate routing contract,
- unsupported locale key → `422`,
- translation/AI proposals cannot set `localeStatus` to `published` on their own.

---

## Frontend

- locale tabs or split view with clear status,
- missing/fallback locale indicators,
- save/publish action identifies the affected locale,
- a `409` conflict explains that the whole resource changed,
- Monaco JSON shows the canonical object and schema errors,
- preview selects a locale without implying published status.

---

## Migration

1. read-only inventory of single-locale documents and existing locale copies,
2. explicit mapping of default locale and slug conflicts,
3. backup under `data/migrations/<id>/`,
4. dry-run report without writes,
5. batch conversion with a journal,
6. index rebuild and API parity test,
7. confirmation before archiving/deleting legacy sources,
8. rollback from the manifest.

Automatic merging of two files with ambiguous identity is prohibited. Such a case is reported for manual resolution.

---

## Out of scope

- automatic machine translation (It.76/77),
- AI agent (It.75),
- locale-specific ACL,
- locale-specific slug/routing without a separate specification,
- simultaneous CRDT editing,
- removing legacy read compatibility in the same iteration.

---

## Tests

- legacy single-locale read → normalized canonical output,
- SK/EN round trip without losing global fields,
- empty draft locale allowed; invalid published locale → `422`,
- resolver and fallback matrix,
- cache/index locale separation,
- OCC conflict during concurrent writes,
- migration dry-run, resume, conflict report, and rollback,
- API explicitly reports resolved locale/fallback,
- Classic single-locale fixture remains functional.

---

## Definition of Done

- [x] Canonical read model and locale fallback locked for legacy + schema v2 shape.
- [x] Locale-scoped write path (`locale` payload key → schema v2 merge, `LocalizedContentValidator`, `LocalizedContentWriter`).
- [x] Canonical schema and locale fallback are locked in SK/EN API documentation ([CONTENT_API.md](architecture/CONTENT_API.md) §15).
- [x] Legacy documents work without mandatory migration (read-time normalization).
- [x] Migration CLI MVP (`content:locale-migrate`: inventory, dry-run, backup, convert, verify, rollback).
- [x] Demo SK+EN migration passes dry-run → convert → verify → rollback (`DemoLocaleMigrationIntegrationTest`).
- [x] The editor manages both locales in one resource view (locale tabs + scoped save).
- [x] Publish and cache are locale-aware.
- [x] Translation/AI cannot automatically publish a locale (`proposalSource` guard + `LocaleContentProposalPolicy`).
- [x] No-SQL SSOT and Classic compatibility remain intact (`ClassicSingleLocaleCompatibilityTest`).

## Follow-ups

[It.76 self-hosted translation](ITERATION_76.md) · [It.77 cloud translation](ITERATION_77.md) · [It.75 AI agent](ITERATION_75.md)
