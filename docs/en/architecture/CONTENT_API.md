---
title: Content API
description: Pages, articles, search, drafts, locks, and publishing lifecycle
icon: material/file-document-edit
---

# 📝 Content API

> **Status:** Public Beta contract with clearly marked It.68–77 extensions  
> **SSOT:** Markdown/JSON content documents; index and cache are derived  
> **Concurrency:** revision/OCC + edit lock + version history

The Content API is the shared boundary for the React editor, public site, and future headless integrations. The on-disk format is not the direct HTTP contract: clients work with a normalized resource model while the backend owns validation, storage layout, audit, versioning, and publish orchestration.

---

## 1. Resource model

Core types:

| Type | Canonical key | Typical fields |
|------|---------------|----------------|
| page | `page:{slug}` | title, slug, content, status, navigation/SEO metadata |
| article | `article:{slug}` | title, slug, content, excerpt, tags, author, date, comment policy, SEO |

A normalized response may contain:

```json
{
  "type": "article",
  "slug": "news",
  "title": "News",
  "status": "published",
  "content": "# Text",
  "contentFormat": "markdown",
  "html": "<h1>Text</h1>",
  "revision": "<revision>",
  "createdAt": "2026-08-01T10:00:00+02:00",
  "updatedAt": "2026-08-02T12:00:00+02:00"
}
```

Rendered `html` is a derived representation. Canonical content and metadata remain in the file SSOT. HTML must be rendered/sanitized according to input trust and must not become a stored-XSS path in admin or public UI.

---

## 2. Reading lists

```http
GET /api/pages
GET /api/articles
```

Recommended explicit query contract:

| Parameter | Default | Rule |
|-----------|---------|------|
| `page` | `1` | integer ≥ 1 |
| `per_page` | settings default | max 100; `perPage` is a legacy alias |
| `status` | identity-dependent | anonymous is server-pinned to `published` |
| `search` | — | minimum length by validator; title/slug/excerpt/tags |
| `sort` | `-updatedAt` | allow-listed fields; `-` means descending |
| `tag` / `filter[tag]` | — | exact normalized article tag |
| `author` / `filter[author]` | — | documented match, not a free-form regex |
| `date_from`, `date_to` | — | validated ISO date and unambiguous timezone policy |
| `locale` | default locale | ⏳ It.73; fallback explicitly marked |

A legacy request without `page` or `per_page` may return the entire list without `meta`. New frontend/headless clients should use paginated mode.

Example:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 143,
    "total_pages": 8,
    "tags": ["news", "php"],
    "total_published": 120
  }
}
```

Admin-only aggregates must not leak draft counts to anonymous clients.

---

## 3. Resource detail

```http
GET /api/pages/{slug}
GET /api/articles/{slug}
```

Rules:

- anonymous: published and public according to ACL/policy only,
- authenticated editor: statuses according to permission and path ACL,
- staff preview must not be stored in public cache,
- missing and unauthorized resources may share a masked 404 policy,
- a slug is decoded once, normalized, and mapped to a canonical key, never directly to a filesystem path.

After It.69 a response may carry `ETag`/`Last-Modified`; clients may send `If-None-Match` and receive 304 with no body.

---

## 4. Create and update

Typical contract:

```http
POST /api/pages
POST /api/articles
PUT /api/pages/{slug}
PUT /api/articles/{slug}
```

```json
{
  "title": "About us",
  "slug": "about-us",
  "content": "# About us",
  "contentFormat": "markdown",
  "editorProfile": "company",
  "editorMode": "markdown",
  "status": "draft",
  "baseRevision": "<revision>",
  "lockToken": "<lock-token>"
}
```

| Field | Rule |
|-------|------|
| `content` | Markdown, HTML, or Tiptap JSON string according to `contentFormat` |
| `contentFormat` | `markdown`, `html`, `tiptap_json`; backend validates and normalizes |
| `editorProfile` | UI/editor capability hint; does not change authorization |
| `editorMode` | preferred editor mode, not a security control |
| `status` | allow-list + permission; draft save and publish are separate use cases |
| `baseRevision` | required for updates according to OCC policy |
| `lockToken` | proof of an active lock when the workflow requires one |
| `locale` | ⏳ explicit target locale; Apply must not overwrite another locale |

The backend ignores client-supplied fields owned by the server, such as actor identity, audit actor, server timestamps, or computed revision. Rejecting unknown write fields by schema is preferred.

---

## 5. Canonical write lifecycle

```text
authentication → authorization/path ACL → schema validation
→ lock + baseRevision check → atomic SSOT write
→ version + audit → index update → cache invalidation
→ event → optional Git/translation/AI follow-up
```

The primary response must distinguish:

- content was not stored,
- content was stored locally,
- a publish job is pending,
- a commit was created,
- push failed.

An optional provider must not receive a raw session, CSRF token, storage path, or secret settings.

---

## 6. Drafts and autosave

```http
GET    /api/drafts/{type}/{slug}
PUT    /api/drafts/{type}/{slug}
DELETE /api/drafts/{type}/{slug}
```

A draft contains at least the resource key, owner, working content, base revision, and timestamp. Autosave:

- does not publish,
- does not bypass permissions,
- does not treat local browser state as a server save,
- uses debounce and cancels stale requests,
- stops blind retries on 409 and opens conflict flow,
- clears or marks the draft according to policy after successful final save.

Another user's draft or locale must not be returned without explicit permission.

---

## 7. Edit locks

The `/api/locks/*` family handles acquire, heartbeat, and release. The exact route shape should be consolidated because historical documentation uses several variants.

A lock is not a replacement for OCC. It protects editing UX, but expiry, browser failure, or background writes still require revision checks.

Minimum fields:

- canonical resource key,
- owner/actor ID,
- opaque lock token,
- acquired/heartbeat/expiry time,
- optional locale branch only when consistent with the revision model.

---

## 8. Conflicts and merge

On revision mismatch the API returns 409 `conflict`. The frontend may offer Mine, Theirs, Both, or manual three-way merge. The next save uses the latest `serverRevision`.

Prohibited behavior:

- automatic overwrite after 409,
- a hidden force-save behind a normal button,
- merging one locale while discarding another,
- leaking server content the actor cannot read.

---

## 9. Status and publishing

Recommended lifecycle:

```text
draft → review/approved according to policy → published → archived
```

The current project may use a smaller status set; the API must reject unknown enums and must not derive publishing solely from the presence of a date.

Sensitive publishing may require an OTP challenge. It.70 adds immediate or queued Git publishing, but local SSOT save remains the first authoritative operation.

UI and API distinguish:

```text
stored | pending_publish | committed | pushed | publish_failed
```

A retry publishes a specific revision and uses an idempotency key; it must not accidentally distribute newer unreviewed content.

---

## 10. Soft delete and trash

```http
DELETE /api/pages/{slug}
DELETE /api/articles/{slug}
```

Delete moves a resource to trash with metadata; it is not permanent deletion. Restore/purge belong in the protected admin trash API.

Soft delete must:

- preserve the original canonical key and revision,
- audit actor and reason when required,
- update index/cache,
- handle a collision when a new resource already uses the slug,
- avoid deleting binary media shared by other content without a reference policy.

---

## 11. Bulk operations

Historical endpoints include bulk delete/status for pages and articles. A bulk request is a set of individually authorized use cases, not one prefix permission.

The response must report success/failure per item. An atomic all-or-nothing mode must not be claimed when the flat-file backend has no transaction journal. Partial success must be explicit and safely retryable.

---

## 12. Search

### Public search

```http
GET /api/search?q=home&scope=public&types=page,article&limit=20
```

- minimum query length according to validator,
- published content only,
- the index is derived and rebuildable,
- a damaged index must not silently look like zero content without diagnostics/fallback policy.

### Admin command palette

```http
GET /api/search?q=set&scope=admin&types=page,article,media,route&limit=8
```

Requires a session and returns only results the actor may view/open. `adminPath` is a navigation hint, not authorization. The It.43 admin search is treated as a delivered foundation at this checkpoint, not “unreleased.”

---

## 13. SEO and comments policy

SEO fields for pages/articles:

| API field | Meaning |
|-----------|---------|
| `seoTitle` | title override; falls back to content title |
| `seoDescription` | meta description |
| `canonical` | validated absolute URL or empty |
| `ogImage` | policy-approved media URL |
| `noIndex` | boolean |
| `tags` | article taxonomy |
| `featuredImage` | article card/hero derivation according to model |

`GET /api/seo/{type}/{slug}` returns only a safe published slice.

Article comment fields may override global policy, but the resolver combines global settings + resource override. A client cannot force display of disallowed comments.

---

## 14. Storage format

`content.storageFormat` may select `md` or `json` for new saves:

| Format | SSOT |
|--------|------|
| `md` | YAML front matter + Markdown body |
| `json` | normalized JSON object with content field |

The API model remains stable regardless of disk format. Migration must not create two authoritative copies of one resource. The index contains derived lookup metadata only.

---

## 15. Locale-aware content — It.73

The planned model must define:

- supported/default locale allow-list,
- explicit requested/effective/fallback locale in responses,
- revision over the canonical multilingual document or a safe per-locale model without lost updates,
- fallback for reads only, not persistence of fallback text as completed translation,
- path ACL and publishing status per locale according to policy,
- cache keys including locale and public/admin variant.

It.76–77 translation creates a proposal/diff; Apply is a separate authorized operation and automatic publishing is outside the base flow.

---

## 16. Headless scopes — It.74

MVP read scope `content:read` exposes only the published headless slice. `content:write` is explicit opt-in and uses the same schema, revision, lock, audit, and publishing policy as session flow.

An API key scope never automatically opens `/api/admin/*`. Route and method must be present in an allow-list map.

---

## 17. Errors

| Situation | Status/shape |
|-----------|--------------|
| invalid filter/payload | `422` + `errors` |
| missing identity | `401` |
| missing permission/scope | `403` |
| missing or masked resource | `404` |
| revision mismatch | `409` + `conflict` |
| active foreign lock | `409` + `lock` |
| upload/body limit | `413` |
| rate limit | `429` |
| maintenance | `503` according to allow-list policy |

After 401 the frontend must not automatically resend a write until session and CSRF state are safely restored.

---

## 18. Frontend wiring

| Capability | Client/hook | UI responsibility |
|------------|-------------|-------------------|
| lists/detail/save | centralized content API module | Pages/Articles manager + editor |
| search | search API | public search + Ctrl+K palette |
| draft | drafts client + autosave hook | saving/saved/conflict state |
| lock | locks client + heartbeat hook | owner/expiry banner |
| versions | versions client | history, compare, restore |
| OTP | workflows client | challenge modal without false success |
| publish | content/Git client | local save vs distribution state |
| locale/translation | ⏳ locale clients | requested/effective locale, diff/Apply |

Specific filenames may change during refactoring; the public contract is behavior and typed interfaces, not today's import path.

---

## 19. Testing

- anonymous list/detail never returns a draft,
- pagination/filter/sort bounds and deterministic order,
- slug/path traversal and double decoding,
- schema for every content format,
- lock + OCC race and repeated 409,
- autosave never publishes a draft,
- soft delete/restore/collision,
- bulk partial failure,
- search ACL and `adminPath` safety,
- SEO URL/media validation,
- locale fallback without data leakage/lost updates,
- failed index/cache/Git/provider with safe fallback,
- session and future API key writes pass the same domain policy.

---

## 20. Related documents

- [API.md](./API.md) — complete HTTP surface
- [API_CONTRACT.md](./API_CONTRACT.md) — envelopes and errors
- [VERSIONING.md](./VERSIONING.md) — draft, lock, revision, merge, restore
- [STORAGE.md](./STORAGE.md) — SSOT, index, cache, and atomic writes
- [FRONTEND.md](./FRONTEND.md) — editor lifecycle and API client
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md) — stable edit/list URLs
