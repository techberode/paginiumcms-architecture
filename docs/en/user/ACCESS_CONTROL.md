---
title: Permissions and Path ACL
description: RBAC, content paths, least privilege, and authorization verification
icon: material/shield-account
---

# Permissions and Path ACL

> PaginiumCMS uses global permissions and optional path-based restrictions. Policy changes should be performed only by a trusted `SUPER_ADMIN` with 2FA enabled.

## 1. Two authorization layers

| Layer | Purpose |
|---|---|
| RBAC / permissions | allows a domain action such as `content:edit` or `media:upload` |
| Path ACL | narrows the permission to a specific flat-file content tree |

A user must pass both layers when Path ACL is active. A frontend route guard is not a third security layer; it is only a UX aid.

## 2. Roles

| Role | Default intent |
|---|---|
| `USER` | public and profile capabilities |
| `EDITOR` | create and edit content and media |
| `ADMIN` | manage platform and users |
| `SUPER_ADMIN` | full administrative and policy access |

Since **It.84d**, SUPER_ADMIN can define **custom roles** stored in `data/roles.json` (`id`, `name`, `permissions[]`, `system`). Built-in roles `ADMIN`, `EDITOR`, and `USER` remain system roles (editable, not deletable). Permission IDs must exist in the backend `PermissionCatalog`. Manage roles at **Security → Custom roles** (`/security/roles`) or sync system-role permissions via **Settings → Access control**.

SUPER_ADMIN bypass is a powerful exception. Use the account for policy/extension tasks, not routine article writing.

## 3. Permission catalog

A current build may include:

| Permission | Meaning |
|---|---|
| `content:view` | read allowed content |
| `content:create` | create content |
| `content:edit` | edit, drafts, locks |
| `content:delete` | soft/permanent delete according to endpoint |
| `content:manage` | umbrella content capability |
| `media:upload` | upload media |
| `media:delete` | delete media |
| `media:manage` | umbrella media capability |
| `user:manage` | manage users |
| `settings:manage` | manage allowed settings |
| `logs:view` | read operational logs |
| `profile:edit` | own profile |

Treat the backend `PermissionCatalog`/API metadata of the concrete release as canonical. Documentation must not be the only source of names.

## 4. Default policy

The default mapping should be a least-privilege bootstrap. Review it after installation and do not grant `settings:manage` to an editor merely to expose one missing screen.

When changing mappings:

1. export or back up current settings,
2. change one role,
3. save and verify runtime reload,
4. test a new session for that role,
5. verify a direct API request,
6. inspect audit.

## 5. Path ACL scope

Path ACL is designed for content paths such as:

```text
content/pages/{slug}
content/blog/{slug}
content/media/{folder-or-file}
```

It is not a universal filesystem firewall for `.env`, logs, backups, or source code. Deployment and storage allow-lists protect those paths.

## 6. Path normalization

Before matching, the backend must canonicalize separators, reject forbidden segments, resolve extensions according to contract, and enforce an allowed prefix. User input must not enable `../`, double decoding, or Unicode-based bypass.

Examples:

| Input | Canonical path |
|---|---|
| `pages/finance/budget.md` | `content/pages/finance/budget` |
| `content/blog/internal/*` | `content/blog/internal/*` |
| `media/team/logo.png` | `content/media/team/logo` |

## 7. Matching rules

Support only explicitly documented forms, such as an exact path and a prefix ending in `*`. Regex, `**`, `?`, or a wildcard in the middle must not be accepted unless the backend implements them.

Rule order must be deterministic. With “first match wins”, place specific rules before general rules and preserve order in the UI.

## 8. Default allow or default deny

The current transitional model may be opt-in and default-allow for unmatched paths. This simplifies migration but is not suitable for every sensitive deployment.

Administrators must know which model a concrete release uses. A future default-deny profile should be explicit and include diagnostics and a recovery account; it must not arrive through a silent migration.

## 9. Examples

Finance section for editors and higher roles:

```text
path: content/pages/finance/*
roles: EDITOR, ADMIN
```

Internal media for administrators:

```text
path: content/media/internal/*
roles: ADMIN
```

Permission-based rule:

```text
path: content/blog/team/*
permissions: content:edit
```

Empty roles and permissions must not mean an ambiguous “deny everyone”. The UI should explain the outcome explicitly or reject the rule.

## 10. HTTP behavior

| Operation | Recommended response |
|---|---|
| read denied/hidden item | 404 when the intent is to hide existence |
| mutation without permission/ACL | 403 |
| unauthenticated staff endpoint | 401 |
| stale revision | 409, not a masked ACL error |

List endpoints must filter items under the same policy as detail endpoints. Otherwise a title or metadata may leak through a list while detail returns 404.

## 11. Media

Media ACL must be evaluated for list, detail, upload destination, move/rename, delete, and signed-URL generation. A public static URL may bypass application ACL; private media must therefore not live in a directly public tree.

## 12. Test scenario

1. create a rule for a test prefix,
2. create an item as SUPER_ADMIN,
3. verify anonymous GET,
4. verify EDITOR list and detail,
5. verify EDITOR write,
6. verify ADMIN according to policy,
7. test both API and UI,
8. remove the rule and verify recovery.

Use separate sessions because an old session or frontend cache may temporarily show stale menu state.

## 13. Lockout recovery

Before enabling a strict policy have:

- a second SUPER_ADMIN account,
- server access to a settings backup,
- a documented path to validate/restore the ACL file,
- an audit record of the change,
- a maintenance window for production.

Edit JSON manually only after stopping write traffic and validate syntax. Run reload/diagnostics after repair.

## 14. Security invariants

- the backend makes every deny decision,
- path canonicalization occurs before authorization and filesystem operations,
- a permission supplied in request body is never trusted,
- batch operations check every item,
- a job/queue carries actor identity and authorized context from the original action,
- audit redacts secrets while recording actor, action, target, and result.

## 15. Related documents

- [Administrator guide](ADMIN_GUIDE.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Content API](../architecture/CONTENT_API.md)
- [Settings](../architecture/SETTINGS.md)
- [ISSUES.md](../ISSUES.md)
