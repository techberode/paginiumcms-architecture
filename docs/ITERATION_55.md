# Iteration 55 – Tiptap JSON storage & media upload

**Status:** ✅ Complete  
**Version:** **2.0.43**

## Summary

WYSIWYG documents persist as **structured Tiptap JSON** (`contentFormat: tiptap_json`) in flat-file storage. PHP validates node types against editor profiles, renders sanitized HTML for public views, and caches `html` on JSON records. Image paste/drop/upload in the editor lands in DAM via existing `/api/media/upload`.

## Deliverables

| Area | Change | Status |
|------|--------|--------|
| Backend | `TiptapHtmlRenderer`, `ContentBodyRenderer` | ✅ |
| Storage | `JsonContentStorage` — cached `html` on save | ✅ |
| Validation | `EditorContentValidator::validateTiptapJson()` | ✅ |
| API | `contentFormat: tiptap_json` on pages/articles | ✅ |
| Frontend | WYSIWYG saves JSON via `getJSON()` / `setContent()` | ✅ |
| Upload | Paste, drop, file picker → `uploadMedia(..., 'editor')` | ✅ |
| Preview | Site preview uses rendered HTML (live WYSIWYG or API cache) | ✅ |
| Auth fix | ISS-042 — `probeSessionWithRetry` after login | ✅ |
| Tests | PHPUnit renderer/validator/body + Vitest `contentEditor` | ✅ |

## Flat-file model

| Field | Location | Format |
|-------|----------|--------|
| `contentFormat` | front matter / JSON record | `markdown` \| `html` \| **`tiptap_json`** |
| `content` / `body` | content file | MD string **or** JSON string |
| `html` | JSON record cache | pre-rendered HTML for public speed |

Example front matter:

```yaml
---
title: Novinka
editorProfile: blog
editorMode: wysiwyg
contentFormat: tiptap_json
---
```

## Backend pipeline

```
POST/PUT /api/pages|articles
  → EditorContentValidator (profile + Tiptap node walk)
  → ContentRepository / JsonContentStorage
  → ContentBodyRenderer → TiptapHtmlRenderer → cached html
```

Public read returns `html` for `tiptap_json` records (same as markdown path).

## Frontend

- `storagePayloadFromEditor()` — WYSIWYG → `tiptap_json` (legacy raw HTML still supported)
- `WysiwygEditor` — JSON round-trip, `storedFormat`, paste/drop upload hook
- `MarkdownEditor` — wires upload + preview HTML from live editor

## Security

- JSON node whitelist mirrors It.54 profile capabilities
- `TiptapHtmlRenderer` strips unsafe URLs (`javascript:`, etc.)
- No `script` / `iframe` nodes

## Acceptance criteria

- [x] Save/load round-trip: Tiptap JSON ↔ disk ↔ public HTML
- [x] Image paste/upload lands in media tree (`editor/` folder default)
- [x] Invalid / disallowed JSON node rejected with 400
- [x] PHPUnit: validator + renderer + ContentBodyRenderer
- [x] No regression for pure Markdown articles
- [x] `./scripts/iteration-gate.sh` green

## Related

- [ITERATION_54.md](ITERATION_54.md) — editor profiles
- [ITERATION_24.md](ITERATION_24.md) — MediaRepository / DAM
- [ISSUES.md](ISSUES.md) — ISS-042 login retry

## Next

→ [Iteration 56](ITERATION_56.md) — rich navigation menu items
