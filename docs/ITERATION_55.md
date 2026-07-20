# Iteration 55 – Tiptap JSON storage & media upload

**Status:** ⏳ Planned (implementation **after It.15**, after It.54)  
**Wave:** Post-15 Editor & UX  
**Priority:** 🔴 High

## Summary

Persist WYSIWYG documents as **structured JSON** in flat-file storage (alongside or instead of Markdown), validate on PHP, and render **static HTML** for visitors. Image upload from Tiptap goes directly into the **Flat-File media** tree (`uploads/` / `MediaRepository`).

## Flat-file model

| Field | Location | Format |
|-------|----------|--------|
| `bodyFormat` | front matter | `markdown` \| `tiptap_json` |
| `body` | content file | MD string **or** JSON string |
| `bodyHtml` | optional cache | pre-rendered HTML for public speed |

Example front matter:

```yaml
---
title: Novinka
editorProfile: blog
editorMode: wysiwyg
bodyFormat: tiptap_json
---
```

Body file may store JSON in `.json` content records or escaped block in `.md` — decision in implementation (prefer existing dual storage from It.19).

## Backend pipeline

```
POST /api/content/... 
  → EditorContentValidator (schema per profile)
  → ContentRepository::save()
  → optional HtmlRenderer::fromTiptapJson() → bodyHtml cache
```

| Endpoint | Purpose |
|----------|---------|
| `POST /api/admin/media/upload` (existing) | Tiptap image handler target |
| `POST /api/admin/editor/upload-image` | Optional thin wrapper with alt/folder defaults |

## Public rendering

- **Dynamic SPA:** hydrate from `bodyHtml` or client-side Tiptap read-only (prefer HTML cache)
- **Static (It.48):** rebuild includes rendered HTML in static export

## Security

- JSON schema whitelist (node types, attrs) — mirror It.54 profiles
- No `script`, `iframe` unless profile explicitly allows embeds
- Sanitize URLs in `link` and `image` nodes

## Dependencies

- ⛔ [It.15](ITERATION_15.md)
- ⛔ [It.54](ITERATION_54.md) — profiles & extension set
- ✅ [It.24](ITERATION_24.md) — MediaRepository / DAM
- 🟡 [It.48](ITERATION_48.md) — static HTML export (optional integration)

## Acceptance criteria

- [ ] Save/load round-trip: Tiptap JSON ↔ disk ↔ public HTML
- [ ] Image paste/upload lands in `content/media/` with correct MIME checks
- [ ] Invalid JSON node rejected with 422 + field errors
- [ ] PHPUnit: validator + round-trip integration test
- [ ] No regression for pure Markdown articles

## Next

→ [Iteration 56](ITERATION_56.md) — rich navigation menu items
