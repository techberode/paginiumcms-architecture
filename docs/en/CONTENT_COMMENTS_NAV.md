# Content, comments, contact, and navigation — functional contract

> **Status:** living reference documentation for admin and public UI  
> **Storage:** No-SQL files; settings and content are written through backend services

This document combines behavior that the old backlog incorrectly presented as entire planned iterations. The foundation for articles, comments, contact, and nested navigation is already shipped; only concrete extensions remain.

---

## 1. Articles — lists, filters, and pagination ✅

The admin article list uses a server-side contract:

- `page`,
- `per_page`,
- text filter,
- status/tag/date/author filters supported by the endpoint,
- sorting,
- URL synchronization for supported filters.

Supported page sizes: **5 / 10 / 20 / 50**. The default comes from `settings.ui.adminListPageSize`.

The public blog uses published-only filters and must not expose draft or private content in anonymous scope.

---

## 2. Global comment settings ✅

The `comments` settings group:

| Key | Type | Meaning |
|-----|------|---------|
| `enabled` | bool | globally enable comments |
| `requireApproval` | bool | new comments wait for moderation |
| `allowGuestComments` | bool | enable anonymous/guest form |
| `maxLength` | int | maximum comment length |

The public projection returned by `GET /api/settings/public` may contain only safe values required to render the form.

---

## 3. Per-article comment policy ✅

Article front matter / API:

| Field | Type | Meaning |
|-------|------|---------|
| `commentsEnabled` | `bool` | enable comments for the article |
| `commentsRequireApproval` | `bool \| null` | `null` = global rule; bool = override |
| `commentsAllowGuests` | `bool \| null` | `null` = global rule; bool = override |

`CommentPolicyResolver` creates the effective policy:

```text
effective.enabled = global.enabled AND article.commentsEnabled
effective.requireApproval = article override ?? global.requireApproval
effective.allowGuests = article override ?? global.allowGuestComments
```

The backend enforces the policy on `POST /api/comments`. The frontend is only a UX layer and must not be the sole protection.

---

## 4. Comment moderation ✅ foundation / 🟡 extensions

Shipped foundation:

- admin list and counts,
- approve/reject/delete according to permissions,
- bulk operations,
- optional email OTP approval workflow,
- global and per-article rules.

Potential remaining backlog:

- finer-grained moderator role,
- CAPTCHA/provider adapter for guest comments,
- anti-spam scoring and quarantine,
- per-article notification subscriptions.

These items must not reuse It.39 without verifying history; they should become new, unambiguously named backlog candidates.

---

## 5. Contact form ✅

Shipped scope includes:

- configurable default subjects in `contact.subjects`,
- custom subject option through `contact.allowCustomSubject`,
- inbox/admin contact management,
- safe public settings,
- company details and optional map embed under allow-list rules.

If the data model exposes priority, the UI and backend must use the same enum and a safe default. Priority must not change authorization or bypass spam/rate-limit rules.

---

## 6. Navigation — tree and nested menu ✅

Primary storage: `data/navigation.json`, or a compatible storage driver after It.68.

The model uses a flat registry with `parentId`; utilities construct a tree for admin and public rendering.

| Rule | Value |
|------|-------|
| Maximum depth | 3 levels unless settings/schema defines a stricter rule |
| Validation | backend on every save |
| Cycles | forbidden |
| Missing parent | reject or safely repair according to an explicit migration policy |
| Duplicate ID | forbidden |

The shipped rich navigation model may include:

- `label`, `path`, `parentId`,
- `description`,
- `iconType` / `iconValue`,
- thumbnail/preview settings,
- `publicRoute` for specialized modules.

---

## 7. Navigation admin UX ✅

- create, edit, and delete items,
- create submenu,
- reorder within allowed depth,
- inline label/path/description fields,
- media picker or icon by type,
- validation before save and readable server errors,
- live preview where available.

The frontend must not be able to store a cycle or excessive depth even when the payload is manually altered; backend validation is authoritative.

---

## 8. Public navigation rendering ✅

- desktop dropdown for nested items,
- mobile indentation/accordion behavior,
- description as secondary text,
- optional icon/thumbnail,
- hover preview only on suitable devices,
- respect for `prefers-reduced-motion`,
- safe internal/external links and target settings.

---

## 9. API and security boundaries

| Operation | Requirement |
|-----------|-------------|
| `GET /api/navigation` | safe public model only |
| `PUT /api/admin/navigation` | session + CSRF + permission + schema validation |
| `POST /api/comments` | rate limit + content validation + effective policy |
| Admin moderation | permission + CSRF + audit |
| Contact | rate limit, sanitization, size limits, generic response |
| Public settings | no secrets, tokens, or internal paths |

---

## 10. Related components

| Area | Typical files/services |
|------|------------------------|
| Article model | `Core/FlatFile/Models/Article.php` |
| Comment policy | `Modules/Comments/Services/CommentPolicyResolver.php` |
| Article comments panel | `frontend/src/components/backend/ArticleCommentsPanel.tsx` |
| Navigation admin | `frontend/src/components/backend/NavigationManager.tsx` |
| Tree utility | `frontend/src/utils/navigationTree.ts` |
| Public navbar | `frontend/src/components/frontend/Navbar.tsx` |
| Contact settings | settings schema + contact controller/service |

Paths are indicative for this snapshot; refactors must update both language editions.

---

## 11. Acceptance checklist

- [ ] globally disabled comments cannot be bypassed with a direct API call,
- [ ] per-article overrides match in API and UI,
- [ ] draft/private articles do not leak through comments or navigation endpoints,
- [ ] a navigation cycle and a fourth level are rejected,
- [ ] the guest form respects rate limits and maximum length,
- [ ] contact does not return internal errors or sensitive data,
- [ ] SK/EN text uses the same keys and the same feature state.
