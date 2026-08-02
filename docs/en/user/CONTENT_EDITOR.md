---
title: Content Editor
description: Pages, articles, drafts, media, SEO, conflicts, and safe publication
icon: material/file-edit
---

# Content editor — pages and articles

> The editor is an API client. The backend performs the authoritative flat-file SSOT write; browser state or local preview is not saved content.

## 1. Content types

| Type | Typical fields |
|---|---|
| Page | title, slug, body, template/layout, status, SEO |
| Article | page fields plus excerpt, tags, featured image, date, and comments |

A blueprint or extension may add fields. Migrations and the API must handle unknown fields deterministically; the editor must not silently discard them.

## 2. Editor modes

- **Markdown**: direct source editing with preview.
- **WYSIWYG/TipTap**: visual editing over a supported schema.
- **SEO/meta panel**: title, description, canonical, OG image, robots policy.

Switching between Markdown and WYSIWYG may be lossy for unsupported HTML or extension nodes. Create a version/backup and review the diff before switching a complex document.

## 3. Slug and identity

A slug should be stable, URL-safe, and unique within its type/locale. Changing it may:

- change the physical/logical document path,
- break internal links,
- require a redirect,
- change Path ACL matching,
- appear as a rename in Git history.

The editor should not rewrite external links automatically without a clear report.

## 4. Draft, published, and archived

| State | Public site | Admin |
|---|---|---|
| `draft` | no, except authorized preview | yes |
| `published` | yes according to time/policy | yes |
| `archived` | no in normal public output | yes |

Scheduled publication is a domain state plus a worker. Saving a future date without a running scheduler may not publish content.

## 5. Save and revision checks

Safe save flow:

```text
auth → permission → validation → lock/revision check
→ atomic SSOT write → version/audit
→ index/cache/event → optional downstream job
```

The client sends a revision/ETag according to contract. For a stale revision the backend returns a conflict; it must not silently use last-write-wins when the endpoint declares OCC.

## 6. Locks and heartbeat

An open document may acquire a temporary lock with TTL and heartbeat. A lock:

- helps coordinate editors,
- does not replace revision checks,
- may expire after a closed laptop or network loss,
- should be released on exit, while the backend must also support TTL recovery.

Force unlock belongs to a privileged role and must be audited.

## 7. Conflict and merge

For a conflict compare:

- base revision,
- your local version,
- latest server version.

Prefer a 3-way merge or explicit selection. Review front matter, media links, custom nodes, and status before applying. Never copy only the visual body when that would discard metadata.

## 8. Autosave

Autosave should store a draft or recovery snapshot, not automatically mutate the published version. UI should show at least `saving`, `saved`, `offline/error`, and conflict states.

Before closing a tab after an error, export or copy the local change. Browser local storage is not a server backup.

## 9. SEO and Open Graph

| Field | Rule |
|---|---|
| SEO title | concise and relevant; content title may be the fallback |
| Meta description | useful summary without keyword spam |
| Canonical URL | trusted absolute URL or empty automatic mode |
| OG/featured image | select from Media Manager and verify public access |
| `noindex` | use intentionally; it is not access control |

`noindex` does not block access to a URL. Protect private content with authorization/ACL and do not publish it.

## 10. Media in content

The media picker should return a canonical identifier or supported URL. Check references before deleting media.

Do not insert untrusted `<script>`, inline event handlers, or `javascript:` URLs. The HTML/Markdown renderer must sanitize output according to policy.

## 11. Featured and OG image

An article may map one media path to `featuredImage`, `ogImage`, or front matter `seoImage` under the transitional API contract. The concrete build documentation is decisive; the editor should not create three different values without a clear reason.

For a 404, check save-after-selection, media route/proxy, public path, and cache.

## 12. Preview

Preview may be:

- a staff-authenticated route,
- a signed short-lived link,
- a local renderer in the admin app.

A preview link must not be a permanent public bypass around draft policy. Never share a session URL or token in an issue/screenshot.

## 13. Bulk operations

Before bulk publish/archive/delete:

1. verify filter and total count,
2. evaluate role and Path ACL for every item,
3. determine whether the operation is atomic or returns per-item results,
4. after partial failure do not blindly repeat successful items,
5. inspect audit.

## 14. Localization

Current admin language and the future multilingual content model are separate. Target It.73 introduces locale-aware identity and variant relationships. It.76/77 translation should create a proposal and diff; **Apply** and **Publish** remain separate authorized steps.

## 15. Git publishing and headless output

Saving to SSOT is a local success. It.70 Git publishing has its own state and may fail after a successful save. The editor should distinguish `stored`, `pending_publish`, `pushed`, and `publish_failed` without losing local content.

## 16. Diagnostics

| Symptom | Check |
|---|---|
| Save returns 409 | revision, lock, second open session |
| Save returns 422 | field validation, slug, blueprint, HTML policy |
| Save returns 403 | permission or Path ACL |
| Preview differs from public | draft session, cache, build/theme difference |
| Image works in admin but not public | public media route/proxy and saved path |
| Changes disappear after mode switch | unsupported Markdown ↔ WYSIWYG conversion |
| Publication remains pending | worker/scheduler or Git job state |

## 17. Related documents

- [Content API](../architecture/CONTENT_API.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Versioning](../architecture/VERSIONING.md)
- [Media and storage](../architecture/STORAGE.md)
- [Permissions](ACCESS_CONTROL.md)
