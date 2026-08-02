---
title: Admin Deep Links
description: Stable contract for admin paths, query parameters, and navigation helpers
icon: material/link-variant
---

# 🔗 Admin Deep Links

> **Goal:** shareable, restorable, backward-compatible administration URLs  
> **Used by:** sidebar, dashboard, Ctrl+K, notifications, audit links, and browser history  
> **Security rule:** a deep link is navigation, not authorization

A deep link opens a specific module, filter, settings group, or audit context after refresh and when sharing a URL. State that should survive reload and is safe for a URL belongs in path/query; transient modal state and secrets do not.

---

## 1. Canonical shapes

| Type | Format | Example |
|------|--------|---------|
| module | `/{module}` | `/media`, `/comments` |
| resource list | `/{module}?…` | `/pages?q=foo&page=2&status=draft` |
| resource detail/edit | stable route from registry | `/pages/about-us/edit` or the canonical project equivalent |
| settings | `/settings?category={category}&group={group}` | `/settings?category=security&group=accessControl` |
| log filter | `/logs?severity={level}` | `/logs?severity=critical` |
| content audit | `/audit/content/{contentId}` | `/audit/content/page-home` |
| user audit | `/audit/user/{userId}` | `/audit/user/editor-1` |
| locale | query/path according to final It.73 contract | `/pages/about-us/edit?locale=en` |

This document does not freeze an unverified detail route by assumption. The route registry and frontend tests must confirm the canonical shape; a helper is the single URL producer.

---

## 2. Query rules

- names are stable ASCII,
- values are encoded through `URLSearchParams`, not manual concatenation,
- default values may be omitted,
- parameter order does not change meaning,
- unknown parameters are ignored or removed with `replace`, not a crash,
- invalid enums/bounds normalize to a safe default,
- multi-value filters have one documented representation,
- an empty search is removed from the URL,
- changing a filter generally resets `page=1`,
- UI respects browser back/forward.

A URL must never contain a password, CSRF token, session ID, API key, reset token, processed OAuth code, full content, raw provider prompt, or internal storage path.

---

## 3. Settings deep links

Settings groups correspond to schema keys. Examples:

| Purpose | Canonical link |
|---------|----------------|
| role permissions | `/settings?category=security&group=accessControl` |
| branding | `/settings?category=site&group=branding` |
| logging | `/settings?group=logging` |
| firewall | `/settings?group=firewall` |
| scheduler | `/settings?group=scheduler` |
| SMTP | `/settings?group=smtp` |
| connectors | `/settings?group=connectors` |
| code policy | `/settings?group=codePolicy` |

If a group does not exist or the actor lacks permission, the UI shows a safe fallback/403 and must not accidentally open the first secret group.

`category` is a presentation hint; `group` is the more stable schema key. Backend permission remains authoritative.

---

## 4. Lists and filters

Recommended parameters:

| Module | Parameters |
|--------|------------|
| pages/articles | `q`, `page`, `per_page`, `status`, `sort`, `tag`, `author`, `date_from`, `date_to` |
| media | `q`, `page`, `folder`, `type`, `sort` |
| comments/messages | `q`, `page`, `status`, `sort` |
| logs | `severity`, `channel`, `q`, `from`, `to`, `page` |
| audit | `type`, `severity`, `actor`, `target`, `from`, `to`, `page` |
| jobs | `status`, `type`, `page` |

The parser should use the same allow-lists as the API client schema. UI aliases normalize to one canonical query key.

---

## 5. Audit links

```text
/audit/content/{contentId}
/audit/user/{userId}
```

IDs are path-encoded and server requests use canonical IDs rather than display title/email. An audit link must not put sensitive user values into query strings. If the target no longer exists, history may remain readable according to retention and permission policy.

---

## 6. Locale-aware links — It.73

A locale parameter represents the **edited/requested locale**, not a claim that fallback text is stored translation.

When locale changes:

- a dirty editor requests save/discard/cancel,
- pending autosave completes or is safely canceled,
- URL changes only after the new state is confirmed,
- unauthorized locale branches do not open,
- cache/query key includes locale.

---

## 7. `returnTo` after login/2FA

Only an internal path beginning with a single `/` is allowed. It:

- contains no scheme/host,
- does not start with `//`,
- remains internal after decoding,
- targets a known admin route or safe fallback,
- contains no secret parameters.

`returnTo` is normalized by a central helper. An unvalidated redirect would create an open-redirect/phishing vector.

---

## 8. Central helper API

Typical helpers:

```ts
settingsGroupPath(group, category?)
logsPath({ severity, channel, page })
auditContentPath(contentId)
auditUserPath(userId)
contentListPath(type, filters)
contentEditPath(type, slug, locale?)
safeAdminReturnTo(location)
```

A helper:

- uses route constants/registry,
- percent-encodes values,
- omits defaults/undefined,
- serializes query deterministically,
- never inserts secrets,
- is covered by unit tests.

Hand-built strings scattered through dashboard, sidebar, and palette cause drift.

---

## 9. Producers and consumers

| Link source | Target |
|-------------|--------|
| sidebar | primary module routes |
| dashboard cards/chips | module + filter |
| LogsManager | logging settings |
| FirewallManager | firewall settings |
| Scheduler | scheduler/jobs settings |
| Notifications | SMTP/connectors |
| Ctrl+K | route/resource `adminPath` |
| audit event | content/user context |
| operation toast | resource detail or job status |

Every producer uses a helper. A consumer reads URL state on mount and popstate and synchronizes UI without an infinite replace loop.

---

## 10. Legacy compatibility

Existing `location.state.group` may be supported temporarily:

```text
query parameter → canonical source
legacy location.state → one-time fallback → replace canonical URL
```

A legacy alias receives a test and deprecation entry. It should not remain forever because it does not survive refresh or sharing.

When a route name changes, use an internal redirect/alias to the canonical path during the migration period.

---

## 11. Security and privacy

A deep link:

- never bypasses route/backend guards,
- never renders raw query as HTML,
- validates path IDs and double decoding,
- does not send PII/secrets to analytics without policy,
- rejects `javascript:`/external schemes in `adminPath`,
- does not use a filesystem path as content ID,
- masks resource existence according to backend 404/403 policy.

A command-palette route result must pass an internal-path validator before navigation.

---

## 12. Testing

- helper output for reserved characters and Unicode slugs,
- parser handling for invalid enums, negative pages, oversized query values,
- default omission and deterministic ordering,
- browser refresh/back/forward,
- settings group + category synchronization,
- legacy `location.state` migration,
- safe `returnTo` and open-redirect cases,
- external/malformed command-palette `adminPath`,
- dirty editor during locale/resource switch,
- unauthorized route opened by deep link,
- no secret fields in URL snapshots.

---

## 13. Related documents

- [FRONTEND.md](./FRONTEND.md) — router, state, and guards
- [API.md](./API.md) — backend route families
- [CONTENT_API.md](./CONTENT_API.md) — list/detail/filter/locale contract
- [SETTINGS.md](./SETTINGS.md) — schema groups
- [CORE_HARDENING.md](./CORE_HARDENING.md) — redirect, XSS, and authorization rules
