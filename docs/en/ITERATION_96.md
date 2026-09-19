# Iteration 96 — Document library & file manager (Office, PDF, text)

> **Status:** ✅ **shipped** in **`v2.1.0-beta.86`** (September 19, 2026) — core slices 96a–96f; optional `media:documents:publish` ACL deferred  
> **Priority:** 🟡 **P1 product** — DAM beyond images/video; safe document workflows for editors  
> **Depends on:** [It.78](ITERATION_78.md) unified upload policy · [It.79](ITERATION_79.md) DAM video · [It.72](ITERATION_72.md) storage drivers · [It.67](ITERATION_67.md) untrusted surfaces  
> **Related:** Media Library today (images/video) — **download** added pre-96 via `GET /api/media/file/{path}?download=1`  
> **Not in scope:** SQL document store, server-side Office macro execution, arbitrary binary execution

---

## Goal

Evolve the **Media Library** into a **file manager** for site documents: upload, browse, download, metadata, optional **in-admin text edit**, and **link/embed** from content — with the same security bar as It.78 (profiles, magic bytes, attachment serving, no stored XSS).

| Today (pre-96) | After It.96 |
|----------------|-------------|
| Primary MIME focus: images, video | + **documents**: PDF, plain text, OpenDocument, Office Open XML (docx/xlsx/pptx) — allow-list only |
| Preview: image/video lightbox | + PDF preview (sandboxed), text preview, “download only” for risky types |
| Public URLs via `/storage/` allow-list | Documents default **admin-only download** or **attachment** on public routes |
| Download: ✅ admin `?download=1` (shipped ahead of 96) | Bulk download, version notes, optional public “published document” flag per file |

---

## Security contract (mandatory)

All slices must follow workspace security baseline:

| Topic | Rule |
|-------|------|
| Upload | `UploadPolicyEngine` profile **`documents`** (extends `media`); magic-byte verify; extension + MIME allow-list; max size per type |
| Active content | **Never** serve PDF/Office/HTML inline on public origin without attachment + `nosniff`; SVG/HTML/XML unchanged (attachment + CSP sandbox) |
| Path traversal | Same as media: normalized paths under media root; Zip imports Zip-Slip checked |
| Public web | Only allow-listed subtrees; `data/`, `logs/` never reachable |
| Text edit | Plain `.txt` / `.md` only in admin; save through sanitizer; no `eval`, no server-side template in uploaded Office files |
| Office | Store as blob; **no** server-side conversion to HTML for untrusted upload; optional client-side preview via sandbox iframe or download-only |
| AuthZ | `media:upload`, `media:delete`, new `media:documents:publish` for public document links |
| Audit | Upload/delete/edit logged via existing security audit patterns |
| SSRF | Stock/import URLs unchanged — `OutboundUrlGuard` |

Regression tests in `scripts/security-regression.sh` for new MIME profiles.

---

## Upload allow-list (initial)

| Type | Extensions | MIME (indicative) | Public inline | Notes |
|------|------------|-------------------|---------------|-------|
| Plain text | `.txt`, `.md` | `text/plain`, `text/markdown` | No | Admin edit slice optional |
| PDF | `.pdf` | `application/pdf` | No | Attachment; optional PDF.js preview in admin |
| OpenDocument | `.odt`, `.ods`, `.odp` | `application/vnd.oasis.opendocument.*` | No | Download / attach to content link |
| Office Open XML | `.docx`, `.xlsx`, `.pptx` | `application/vnd.openxmlformats-officedocument.*` | No | Magic-byte OOXML (ZIP + `[Content_Types].xml`) |

Explicit **deny:** `.doc`, `.xls`, `.ppt` (legacy OLE), `.html`, `.svg` as documents (existing media rules), executables, archives unless separate import flow.

Settings (Engine or Media group): toggles per family, max MB, “allow public document URLs”.

---

## Slices

| Slice | Deliverable |
|-------|-------------|
| **96a** | `documents` upload profile + `MediaFormats` extension; repository stores MIME/folder/tags; list filters `type=document` |
| **96b** | File manager UI: table/card columns (type icon), bulk download (zip on server or sequential client), folder UX parity with media |
| **96c** | **Text editor** — open `.txt`/`.md` in admin (CodeMirror 6), save via `PATCH /api/media/{path}` with OCC/version bump; conflict handling |
| **96d** | **PDF preview** in admin (sandbox iframe or pdf.js bundle self-hosted); public site = link with `Content-Disposition: attachment` |
| **96e** | Content integration — picker “Insert document link”, shortcode/block `[document href=…]`, optional “published document” ACL |
| **96f** | Policies UI in Settings — allow-list, quotas, virus-scan hook (optional ClamAV adapter stub), SK/EN help + ContextHelp (It.94) |

**Pre-96 shipped:** authenticated download button in Media Library (`download=1`).

---

## API sketch

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/media/file/{path}?download=1` | ✅ attachment download (existing) |
| POST | `/api/media/upload` | extend with `documents` profile |
| PATCH | `/api/media/{path}/content` | text body replace (96c) — CSRF + `media:upload` |
| GET | `/api/media/{path}/content` | read text for editor (96c) |
| POST | `/api/media/bulk-download` | optional zip export (96b) — size cap |

---

## Definition of Done

- [x] Upload + list + download for allow-listed document types; rejects legacy `.doc` and binaries outside list.
- [x] Public serving fails closed (attachment only) for document MIME types.
- [x] Admin can edit and save `.txt`/`.md` with optimistic concurrency (sidecar version).
- [x] Markdown editor inserts bundled `[document-link]` shortcode from Media Library picker.
- [x] Settings document policy SK/EN + ContextHelp doc links (It.94).
- [x] PHPUnit + gate (bulk ZIP, text content, storage attachment, shortcode expansion).

---

## Related

- [ITERATION_78.md](ITERATION_78.md) · [ITERATION_79.md](ITERATION_79.md) · [STORAGE.md](architecture/STORAGE.md)  
- [ITERATION_94.md](ITERATION_94.md) — contextual help on policy fields  
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)
