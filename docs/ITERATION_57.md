# Iteration 57 – Auto tags & description generator

**Status:** ✅ Shipped (**v2.1.0-beta.4** target) · Wave Post-15 Editor & UX  
**Priority:** 🟡 Medium

## Summary

Help editors fill **tags** and **meta description / excerpt** from article or page body — rule-based first (no mandatory external AI), optional hook for plugins later.

## Scope

| Feature | Input | Output |
|---------|-------|--------|
| **Tag suggestions** | title + body (MD or plain text from Tiptap) | up to N tags (`tags[]`) |
| **Description generator** | first paragraphs + headings | SEO `description`, card excerpt |
| **Manual override** | always | editor can reject/edit before save |

## Algorithm (v1 — deterministic)

1. Strip markup / Tiptap JSON → plain text
2. Tokenize (SK/EN stopwords configurable in Settings)
3. Rank keywords by frequency + title overlap
4. Description: first ~155 chars at sentence boundary

Optional v2 (plugin via It.15): LLM provider hook — **never required for core**.

## UI

- Editor sidebar panel: **“Navrhnúť tagy”** / **“Generovať popis”**
- Preview diff before applying to front matter / SEO panel
- Settings: `content.autoTagEnabled`, `content.autoTagMax`, `content.autoDescriptionEnabled`

## Backend

| Item | Description |
|------|-------------|
| `ContentMetaGenerator` service | pure PHP, testable |
| `POST /api/admin/content/suggest-meta` | `{ type, slug?, title, body, bodyFormat }` → `{ tags, description }` |
| Rate limit | prevent abuse on large bodies |

## Dependencies

- ⛔ After [It.15](ITERATION_15.md) — optional AI plugin
- 🟡 [It.54/55](ITERATION_54.md) — Tiptap plain-text extraction
- ✅ SEO panel (It.27), `ArticleTagsEditor` (It.51)

## Acceptance criteria

- [x] Suggest tags returns sensible SK sample for blog post fixture
- [x] Generated description ≤ configured max length
- [x] Does not overwrite fields without explicit user click “Apply”
- [x] PHPUnit unit tests for generator (no network)
- [x] Works for Markdown articles today; WYSIWYG after It.55

## Next

→ [Iteration 58](ITERATION_58.md) — page layout builder
