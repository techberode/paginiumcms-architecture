# Iteration 80 — SEO redirects, ops integrations & operator toolkit

> **Status:** ⏳ planned · checklist tracking active  
> **Priority:** 🟡 (high impact / moderate effort items first)  
> **Wave:** Product & ops (post-HE-5) · independent of Hybrid Engine core  
> **Depends on:** stable Classic/headless content model ([It.73](ITERATION_73.md), [It.74](ITERATION_74.md) recommended)  
> **Snapshot:** 2026-08-09 · `v2.1.0-beta.30`

## Goal

Ship a **prioritized bundle** of operator-facing features that improve SEO, spam resistance, integrations, compliance, and bulk operations — without a second content model or SQL storage.

This iteration is intentionally **checklist-driven**: each row has status, remarks, and acceptance criteria. Sub-phases (`80a`–`80g`) may ship in separate beta releases.

---

## Priority rationale (impact / effort)

| Order | Sub-phase | Rationale |
|------:|-----------|-----------|
| 1 | **80a** Redirect manager | Largest SEO win for smallest flat-file + middleware scope |
| 2 | **80b** 404 tracking | Natural extension once redirect middleware exists; reuses audit/metrics patterns |
| 3 | **80c** Comment spam heuristics | Low cost before public comment volume grows |
| 4 | **80d** Outbound webhooks | Fits existing Scheduler/Jobs + headless event direction |
| 5 | **80e** GDPR export/anonymize | Legal baseline when EU users exist in comments/newsletter |
| 6 | **80f** CLI toolkit | CI/bulk ops without HTTP session; complements It.74 keys |
| 7 | **80g** CMS import | Onboarding/migration; larger scope, lower urgency |

---

## Master checklist

| ID | Feature | Priority | Status | Impact / effort | Description | Remarks / proposals | Depends on |
|----|---------|----------|--------|-----------------|-------------|---------------------|------------|
| **80a** | Redirect manager (301/302) | 🟡 P1 | ✅ shipped (`beta.32`) | **High / Low** | Flat-file map `data/redirects.json`; middleware before 404; admin UI for `old_path → new_path` (+ optional 302). | nginx SPA hook optional for slug-level prod redirects. | — |
| **80b** | 404 tracking report | 🟡 P2 | ✅ shipped (`beta.34`) | **Med / Low** | Log anonymous 404 hits (path, referer sanitized, UA hash, day bucket); admin dashboard table + CSV export. | Reuse `AccessLogService` / `PerformanceSampleStore` / audit sanitization patterns. Skip admin/API paths noise. | **80a** (shared middleware hook) recommended |
| **80c** | Comment spam heuristics | 🟡 P3 | ✅ shipped (`beta.35`) | **Med / Low** | Honeypot field + rate/heuristic score in `CommentPolicyResolver`; quarantine or reject before public scale. | No CAPTCHA vendor lock-in in MVP; optional Akismet-style adapter later. Regression tests for legit comments. | existing comment policy |
| **80d** | Outbound webhooks | 🟡 P4 | ⏳ planned | **Med / Med** | Events `content.published`, `content.updated` → queued POST to registered URL (Slack/Discord/Zapier). | `OutboundUrlGuard` mandatory; HMAC signing secret; retry + dead-letter in flat-file queue; no payload secrets in logs. | Scheduler/Jobs · [It.74](ITERATION_74.md) optional for remote receivers |
| **80e** | GDPR export / anonymize | 🔵 P5 | ⏳ planned | **Med / Med** | `GET /api/users/{id}/export` aggregates user data from flat-file sources; admin anonymize action for comments/newsletter/subscriber rows. | Not a full DPA product; document limits (backups, logs retention). Permission + audit on export. | user/newsletter/comment stores |
| **80f** | CLI toolkit | 🔵 P6 | ⏳ planned | **Med / Med** | `bin/paginium` or extend `bin/console`: `content:import`, `content:export`, `user:create`, `redirect:validate`. | Prefer same validators as HTTP; no bypass of Path ACL unless `--system` + SUPER_ADMIN shell. Useful with It.74 keys in CI for HTTP path. | console bootstrap |
| **80g** | Import from other CMS | 🔵 P7 | ⏳ planned | **High / High** | WordPress XML, Jekyll Markdown, Ghost JSON → flat-file pages/articles/media refs. | Phase 1: pages+posts only; dry-run; idempotent slug map; media URLs rewritten not downloaded in MVP. | **80f** CLI · [It.73](ITERATION_73.md) locale rules |

### Status legend

| Symbol | Meaning |
|--------|---------|
| ⏳ planned | Spec agreed; not started |
| 🚧 in progress | Active implementation |
| ✅ done | Shipped in a tagged release |
| ⏸️ deferred | Consciously postponed |
| 💡 proposal | Idea only; needs scope approval |

---

## 80a — Redirect manager (detail)

### Data model (`data/redirects.json`)

```json
{
  "schemaVersion": 1,
  "rules": [
    {
      "id": "red_…",
      "from": "/blog/old-slug",
      "to": "/articles/new-slug",
      "status": 301,
      "enabled": true,
      "createdAt": "…",
      "note": "Renamed during import"
    }
  ]
}
```

- `from` — path only (no scheme/host); normalized trailing slash policy documented.
- `to` — internal path or relative URL; external targets **denied by default**.
- Atomic write + lock (same pattern as `ApiKeyStore`).

### Runtime

- Middleware early in public stack (before SPA fallback 404).
- On match → redirect response with correct status; emit optional audit metric.
- On miss → continue; **80b** may record 404.

### Admin

- Route `/platform/redirects` (or Settings → SEO group): list, create, disable, test hit.
- Permission: `settings:manage` or new `redirects:manage`.

### DoD (80a)

- [x] 301/302 from flat-file map works on public site (PHP middleware + `/api/public/redirect-resolve`)
- [x] Loop and self-redirect rejected at save time
- [x] Admin CRUD + CSRF + RBAC (`redirects:manage`)
- [x] PHPUnit: match, miss, loop detection, external URL blocked
- [ ] Production nginx hook before SPA fallback (see deploy note below)

---

## 80b — 404 tracking (detail)

- Store aggregated counters in `data/metrics/404_hits.json` or extend existing metrics file (avoid per-hit write storm — bucket by day+path).
- Admin: `/analytics` tab or Security → 404 report; filter 7/30 days; export CSV via `LogSanitizer`.
- Suggest new redirect rule from top 404 paths (UX link to 80a form prefilled).

### DoD (80b)

- [x] 404 on public routes logged with sanitized fields.
- [x] Admin report renders top paths (`/analytics` → 404 tab).
- [x] No PII/secrets in export (`LogSanitizer` in store + CSV cells).
- [x] UX link to redirect form prefilled (`/platform/redirects?from=`).

---

## 80c — Comment spam heuristics (detail)

- Hidden honeypot field in public comment form (must stay empty).
- Score: link count, repetition, velocity per IP/hash, disposable email domain list (static file).
- Actions: `allow` | `quarantine` | `reject`; admin queue for quarantine.
- Wire into existing `CommentPolicyResolver` — no duplicate moderation stack.

### DoD (80c)

- [x] Obvious bot comments blocked; legit comment regression test passes.
- [x] Honeypot filled → silent reject or 422 (silent 201 like newsletter).
- [x] Quarantine status visible in admin comments inbox.

---

## 80d — Outbound webhooks (detail)

- Registry `data/webhooks.json`: URL (HTTPS only via guard), events[], secret, enabled.
- Emit from content publish/update hooks (same as Git publish hooks conceptually).
- Delivery via existing job runner; exponential backoff; `WebhookDeliveryStore` flat-file log.

Events MVP: `content.published`, `content.updated` (page + article).

### DoD (80d)

- [ ] Register webhook in admin; test ping button.
- [ ] Publish triggers signed POST; failure retried; SSRF guard on URL.
- [ ] Secret never returned after create (copy-once pattern like API keys).

---

## 80e — GDPR export / anonymize (detail)

- Export JSON/ZIP: user profile, comments by author, newsletter prefs if linked.
- Anonymize: replace PII with stable pseudonym `anon_<hash>`; retain content if legally allowed with redacted author.
- Admin-only; audit event `gdpr_export`, `gdpr_anonymize`.

### DoD (80e)

- [ ] Export downloadable by authorized admin.
- [ ] Anonymize irreversible for email/name fields in primary stores.
- [ ] Documented in `docs/en/developer/SECURITY.md` retention section.

---

## 80f — CLI toolkit (detail)

Commands (MVP):

| Command | Purpose |
|---------|---------|
| `content:export [--type=page\|article]` | stdout or directory export |
| `content:import [--dry-run]` | validate + write SSOT |
| `user:create` | bootstrap operator account |
| `redirect:validate` | lint redirect map (80a) |

Run as `www-data` or deploy user; uses same services as HTTP layer.

### DoD (80f)

- [ ] Documented in `docs/en/developer/TESTING.md` or CLI doc.
- [ ] Import dry-run never writes; run requires flag.

---

## 80g — CMS import (detail)

Phase 1 importers:

| Source | Input | Output |
|--------|-------|--------|
| WordPress | WXR XML | pages/articles markdown/json |
| Jekyll | `_posts/*.md` | articles |
| Ghost | JSON export | articles |

Slug collision policy: `import-{slug}` or map file in `data/migrations/import-{id}/`.

### DoD (80g)

- [ ] One reference importer (WordPress) end-to-end with dry-run.
- [ ] Imported content visible in admin; no admin route auto-created.

---

## Related fixes (shipped in beta.31 — not It.80 scope)

| Item | Status | Release |
|------|--------|---------|
| API keys nav visible for ADMIN (not only SUPER_ADMIN) | ✅ done | `v2.1.0-beta.31` |
| Pepper/JWT missing banner + API error surfacing | ✅ done | `v2.1.0-beta.31` |
| `AuthorizationManager` auto-append `api-keys:manage` for ADMIN | ✅ done | `v2.1.0-beta.31` |

See [RELEASE_2_1_0_BETA_31.md](RELEASE_2_1_0_BETA_31.md).

### Production nginx (80a remainder)

Default nginx serves SPA `index.html` for public slugs without hitting PHP. For slug-level 301 on production, proxy a lookup before SPA fallback:

```nginx
location / {
    set $redirect_check 0;
    if ($request_method = GET) { set $redirect_check 1; }
    if ($request_uri ~ ^/api/) { set $redirect_check 0; }
    # Optional: auth_request / internal subrequest to /api/public/redirect-resolve?path=$uri
    try_files $uri $uri/ /index.html;
}
```

Until nginx is extended, redirects apply to requests that reach PHP (dev/proxy paths). Resolve endpoint: `GET /api/public/redirect-resolve?path=/old-slug`.

---

## Out of scope (It.80)

- SQL redirect or webhook store.
- Public open redirect URLs without admin allow-list.
- Full GDPR legal suite (cookie consent already elsewhere).
- Replacing It.74 headless API with webhooks (complementary).

---

## Recommended delivery slices

```text
beta.31  — ✅ API keys UX fix pack (shipped)
beta.32  — ✅ 80a redirect manager (shipped)
beta.33  — ✅ deploy pipeline fix (shipped)
beta.34  — ✅ 80b 404 tracking (shipped)
beta.35  — ✅ 80c comment spam heuristics (shipped)
beta.36+ — 80d / 80e / …
```

Adjust after first slice estimate.

## Related

[ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) · [It.74 API keys](ITERATION_74.md) · [It.78 Upload security](ITERATION_78.md) · [developer SECURITY](developer/SECURITY.md)
