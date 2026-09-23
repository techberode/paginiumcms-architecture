---
title: Administrator Guide
description: Roles, modules, routine workflows, and safe PaginiumCMS administration
icon: material/view-dashboard
---

# PaginiumCMS Administrator Guide

> Functional administration index for **`v2.1.0-beta.*`**. Module visibility depends on role, permissions, configuration, and the concrete build.

## 1. Core rules

- Backend authorization is decisive; sidebar items and buttons are only UX.
- Create a backup and understand rollback before a critical change.
- Never copy secrets into issues, audit records, or public screenshots.
- `SUPER_ADMIN` is not a normal day-to-day editor role.
- Content save, publication, Git push, translation, and AI apply are separate actions.

## 2. Roles

| Role | Typical tasks | Should not do |
|---|---|---|
| `USER` | profile and public interactions according to policy | admin mutations |
| `EDITOR` | pages, articles, media, navigation | user and security administration |
| `ADMIN` | platform settings, users, inbox, operations | bypass extension policy or Path ACL without justification |
| `SUPER_ADMIN` | RBAC, Path ACL, extensions, critical settings | everyday editing under a privileged account |

See [ACCESS_CONTROL.md](ACCESS_CONTROL.md) for the exact mapping.

## 3. Dashboard

The dashboard is an orientation view, not a monitoring system with guaranteed completeness. It typically shows content counts, recent activity, storage information, log/firewall state, and quick links.

When a panel fails, inspect its individual API endpoint and logs. Analytics availability must not block authoritative content editing.

### Quick search (command palette)

It.43 admin search lets staff jump to pages, articles, media, admin modules, **settings fields**, and **help text** from the admin UI catalogs.

| Action | How |
|--------|-----|
| Open palette | **Ctrl+Shift+K** (recommended on Firefox/Linux) or **Ctrl+K** in Chromium; or click **Quick search…** in the admin header |
| Navigate results | ↑↓ select, **Enter** open, **Esc** close |
| Empty query | Shows recent jumps + admin module shortcuts (sidebar catalog) |
| Search | Type ≥2 characters — merges **server** hits (`scope=admin`: content, media, routes) with **client** index (settings labels/help, checklist, all registered **admin** i18n modules except `public` / `setup`) |

**Admin vs public site:** The command palette is **admin-only**. Public visitors use the site search modal, which calls `GET /api/search?scope=public` (published pages/articles only). The two scopes never mix in one UI.

Requires an active admin session. If server hits are missing, check the browser network tab for `GET /api/search?scope=admin` (must be **200**, not 401). Client-side matches can still appear when the API returns an empty list. See [ISS-158](../ISSUES.md#iss-158) for the historical 401 bug.

### System update banner (SUPER_ADMIN only)

On **Dashboard → Overview**, SUPER_ADMIN sees an indigo **system update** card when the instance is not in demo mode.

| Behavior | Detail |
|----------|--------|
| **Automatic compare** | **Once per browser session** after login (first time the banner mounts). Not on every dashboard visit. **Logout** resets this so the next session can compare again. |
| **Recheck** | Always runs a fresh GitHub compare (`GET /api/admin/system/update/check`). |
| **Settings → System update → Remote version check interval** | Default **`0`** = no background polling. Values **> 0** only show a **stale** hint when the last compare is older than that many hours — they do **not** auto-call GitHub on a timer. |
| **Deploy** | Available only when an update is reported **and** deploy readiness is green (stack path, token/key, job registered). Same rules as **Platform → System update**. |

Operator checklist: [DEPLOY.md §12.5](../deploy/DEPLOY.md#125-admin-ui-deploy-platform--system-update--dashboard-banner). Common failures: [§12.6](../deploy/DEPLOY.md#126-common-production-symptoms-ops), [ISS-161](../ISSUES.md#iss-161), [ISS-177](../ISSUES.md#iss-177).

## 4. Pages

**Pages** manage public site pages.

Typical workflow:

1. create a draft,
2. edit with a lock/heartbeat,
3. preview,
4. handle a revision conflict,
5. publish or archive,
6. add to navigation.

A slug is part of the URL and file identity. Changing it may require redirects and link verification. See [CONTENT_EDITOR.md](CONTENT_EDITOR.md).

**Page layout builder:** Settings → Layout chooses Templates, Shortcodes, **Block outline** (palette + forms; recommended for landings), or Developer. All modes store the same Markdown. Outline applies to pages only. Fullscreen **Workspace** hides admin chrome (Settings → Editor, or the toggle in the editor). Gallery photos: [GALLERY.md](GALLERY.md).

**List pagination:** The pages table (and the articles table, same component) uses Previous/Next at the bottom. The current page is in the URL (`/pages?page=2`). Changing filters or page size returns you to page 1; Next/Previous must not snap back to the first page ([ISS-169](../../ISSUES.md#iss-169)).

## 5. Articles

Articles use the same editor core and can additionally contain excerpt, tags, featured image, publication time, and comment policy. Scheduled publication requires host cron **`php backend/bin/console scheduler:run`** every minute (job **`content.scheduled_publish`**) — see [CRON.md](../deploy/CRON.md). If **OTP publish approval** is enabled in Settings → Workflows, scheduled items need a saved schedule (sets `publishApprovedAt`) or cron skips them with `otp_not_approved`. Logged-in admins may preview scheduled articles on `/blog`; anonymous visitors only see **`published`** (or locale **`published`**) items.

**Blog index (`/blog`):** The article list is a dedicated SPA route (not a row under `/pages`). To customize the hero/intro, create a **published page** with slug **`blog`** (Pages → New). Its body renders above the article grid; the CMS bar on `/blog` opens that page for editing. Menu link **`/blog`** still points at the list; do not expect a separate `/{slug}` URL for slug `blog`.

**Bulk actions:** When you select rows, the bulk bar shows **“X of Y selected”** (Y = total records matching current filters). Confirm dialogs for publish, draft, archive, and delete repeat that ratio (e.g. “Publish 3 of 47 selected items?”). Always verify filters before bulk publish or delete.

**Print on public blog:** Settings → Content → **Enable article print** (`content.articlePrintEnabled`, default off). When on, visitors see **Print article** on blog detail; printing hides navigation, comments, and chrome.

Use bulk actions only after checking filters and item counts. With soft delete, inspect trash and retention before permanent removal.

## 5.2 Inbox

Same first-segment URLs as the rest of the admin ([ADMIN_DEEP_LINKS.md](../architecture/ADMIN_DEEP_LINKS.md)):

| Item | Path |
|------|------|
| Comments | `/comments` |
| Contact messages | `/messages` |
| Domain IMAP mail | `/mail` |
| Support Kanban | `/kanban` |
| Newsletter | `/newsletter` |

Bookmarks under `/platform/mail` or `/platform/kanban` redirect to the paths above. On a Kanban card you can set **tags**, an **SLA due** date, insert a **canned reply** into the ticket body, and add **internal notes** that stay staff-only (Settings tab stores the reply templates). IMAP settings: `/settings?group=imap` (including **Messages fetched from server** — how many newest messages load per folder, default 40; **Append copy to Sent (IMAP)** — disable for faster send when SMTP alone is enough).

**Mail labels and local trash (`beta.79`):** Sidebar **Labels** — create names and colors, edit/delete (including tags that exist only on IMAP). On an open message, click a label chip (**×**) to remove it; bulk-select rows and use **Remove from selected**. **Local trash** holds messages hidden in this client only; **Empty local trash** permanently dismisses them (they will not reappear in INBOX or trash here; IMAP copies stay on the server). Use **Refresh** to reload folders after external changes (no automatic polling).

**Mail privacy:** HTML messages open in a sandboxed iframe. **Remote images are off by default.** **Show images from …** remembers the sender per active mailbox (this browser). **Block sender** applies to the **active mailbox only**, moves the message to server spam, and stops listing mail from that address in this client. Open **Blocked senders** in the mail sidebar to review the list and **Unblock** an address. Each **page reload** runs **spam autoclean** (server spam folder snapshot is purged from the UI and hidden on later loads). Requires **`APP_KEY`** before saving mailbox passwords ([ISS-170](../ISSUES.md#iss-170)).

**Mail signature (93m-4):** Sidebar **Mail signature** — pick one of six templates, edit fields (or **Load from profile**), enable **Attach signature to outgoing messages**. Settings are stored **per active mailbox** (e.g. `info@` vs your login mailbox). Avatar and shared profile fields live under **My account**; extra mailboxes do not need separate CMS users.

**Mobile mail:** Use the **mail menu** (hamburger in the message list, below the admin header) for folders, compose, signature, and blocklist. The inbox shows the message list only; tap a row to open the full message and **Back to list** to return.

## 5.1 Project site planner

**Workspace → Project planner** (`/project-planner`) is the Full CMS milestone board: phases, due dates, and on-time / late / overdue badges. It does **not** replace the editorial calendar (already-scheduled content) or Origin Panel (maintainer catalog).

Editors with `project-plan:manage` can create plans and items (page/article templates in the add-item flow). Disable via Settings → Site → Project planner. See [PROJECT_PLANNER.md](PROJECT_PLANNER.md).

## 6. Media

Media Manager handles uploads, metadata, folders, and asset selection for content or branding.

An administrator checks:

- allowed MIME type and actual file content,
- file-size limit and disk capacity,
- alt/title metadata,
- public versus internal path,
- references before deletion,
- proxy/storage configuration when a file returns 404.

**Image optimization (`v2.1.0-beta.67`):** For JPEG, PNG, and WebP rasters, open the metadata modal to inspect file size and dimensions. Use **Preview optimize** to compare original vs re-encoded output and estimated savings before saving. Presets (1920 / 1280 / 1080 / 960 px width) scale height proportionally. A quick **⚡ optimize** on the media card applies immediate re-encode when GD is available. If the API returns “already optimally compressed”, the file will not shrink further — try resize instead. Requires PHP **GD** with JPEG/PNG/WebP support in the backend container.

Profile **avatars** are normalized server-side (max 512×512 px, 512 KB); the UI may accept uploads up to 2 MB.

A future local/S3 driver from It.72 does not change the rule that authoritative metadata remains under CMS control and migration must be verifiable.

## 7. Navigation

Navigation defines the public link tree, order, parent relationships, and targets. Export or back up configuration before a large change. Test desktop, mobile, and keyboard navigation.

## 8. Comments and messages

**Comments** support moderation under global and per-content policy. **Messages** form the contact inbox.

Bulk workflow (read, processed, archive, delete) uses the same **“X of Y selected”** bar and confirmation pattern as content lists.

- do not export personal data without a valid reason,
- combine rate limits, WAF, and moderation for spam,
- an urgent label does not replace a notification channel,
- deletion may be subject to retention policy.

## 9. Settings

Settings are divided into groups. Typical areas:

| Area | Examples |
|---|---|
| Site | name, URL, language, timezone, branding |
| Layout | page builder mode (templates / shortcodes / outline / developer) |
| Content/SEO | editor, pagination, metadata, feeds, **article print toggle** |
| Accounts/Security | registration, passwords, 2FA, upload policy |
| Access control | RBAC and Path ACL for SUPER_ADMIN |
| Integrations | SMTP, ntfy, webhook, or another provider |
| Operations | logging, firewall, cache, maintenance |

Sensitive fields should be encrypted at rest and redacted in responses. Rotating `APP_KEY` without a migration procedure may make them unreadable.

## 10. Users and account security

Assign the lowest necessary role, require a unique password, and enable 2FA for staff. When a person leaves, deactivate the account, revoke active sessions/tokens, and review ownership of unfinished content.

The account-security screen may manage password, TOTP, and recovery codes. Disabling 2FA for a privileged account should be audited and protected by reauthentication.

## 11. Notifications and outbound providers

Configure email, ntfy, Telegram, or webhook only for allow-listed HTTPS destinations according to policy. A send test must redact secrets and must not log a full payload containing personal data.

Distinguish successful configuration save from successful delivery. A provider may be unavailable even when CMS validation passed.

### CMS AI assistant and self-hosted translation (It.75 / It.76)

Both features default **off** and send data only when enabled. They never publish automatically; **Apply** is a separate confirmed write.

| Setting group | Purpose |
|---------------|---------|
| **CMS AI assistant** | Ollama or OpenAI-compatible LLM; comma-separated **allowed tools** (empty = no capabilities). |
| **Translation** | LibreTranslate (your instance URL), or DeepL/Google with vendor API keys. |

**Homelab without new WAN ports:** run Ollama or LibreTranslate on `127.0.0.1` on the CMS host and add an **nginx** `location` on your existing HTTPS vhost (for example `/internal/llm/` → port 11434). Set `baseUrl` to `https://<your-site>/internal/llm` — not `http://192.168.x.x` and not container `127.0.0.1`. Use **Test connection** after save.

Long assistant runs need **`worker:process`** cron (see §12). Runbook: [AGENT_OPERATIONS.md](../runbooks/AGENT_OPERATIONS.md).

## 12. Scheduler and jobs

The scheduler UI displays defined jobs; actual execution depends on cron/worker processes. Monitor last run, next run, duration, lock, and last error.

Do not trigger a long job repeatedly just because the UI appears unresponsive. Check workers and logs first to avoid duplicate mail, backups, or Git publication.

Queued handlers include **`agent.run`** (CMS AI assistant) and translation jobs — if cron runs only `scheduler:run` but not `worker:process`, assistant runs stay queued.

## 13. Backups and restore

A backup must cover authoritative content, settings, required keys, and namespaced extension data. Cache and index are rebuildable and should not be the sole recovery source.

On **Platform → Backups** you can include the whole content tree or only pages, articles, media, CMS data, navigation, trash, and/or app config. **Incremental** backups store only files that changed since the last snapshot of the same scope (hash comparison, not the `rsync` binary). Restore of an incremental needs the matching full baseline in the library.

### Automatic scheduled backups

Scheduled backups do **not** run from the browser alone. Enable all of the following:

1. **Settings → Job scheduler** — master switch on.
2. **Platform → Scheduler** — enable the `backup-scheduled` job (default cron: daily at 02:00).
3. **Host cron** — run `php backend/bin/console scheduler:run` every minute (see `docs/deploy/CRON.md`).
4. **Platform → Backups** — choose **what to include** and **full vs incremental**, then open **Automatic backups**, set interval and retention, save. Scheduled runs use the same scope and type.

The UI shows `next_run` / `last_run` after the schedule is saved. Manual **Run now** on the job is useful for testing; production still needs cron.

Safe restore:

1. verify checksum and compatibility,
2. enable maintenance or stop write traffic,
3. create a pre-restore snapshot,
4. restore into a staging path,
5. run diagnostics,
6. activate restored data only after validation.

## 14. Trash and permanent deletion

Soft delete permits recovery. Permanent deletion is an irreversible domain action even when a filesystem backup exists. Before bulk deletion verify filters, item counts, media references, and retention requirements.

## 15. Git synchronization and publishing

Existing GitHub/Git workflows may vary by build. The target It.70 architecture distinguishes:

```text
stored → pending_publish → committed → pushed
                         ↘ publish_failed
```

A local save must not be marked failed merely because a remote Git push failed. Retry must not create a duplicate commit without an idempotency rule.

## 16. Feature gallery

Screenshots live in **Feature gallery** (`/gallery`), not in the page form. Put `[feature-gallery]` on a page (Outline palette → **Gallery**) to show published items. Tags filter the same catalog; they do not create extra stores. Full walkthrough: [GALLERY.md](GALLERY.md).

## 17. Translation and localization

Admin UI translation management is not the same as a multilingual content document. The target It.73/76/77 localization branch uses proposal and diff; translated content must not be published automatically without a separate approval.

## 18. Firewall, logs, and audit

- [Firewall](FIREWALL.md) blocks defined probe scenarios and manages jails.
- [Logs](LOGGING.md) diagnose requests and runtime events.
- Audit records meaningful user and system changes.
- [Code policy](../architecture/CODE_POLICY.md) governs plugins, themes, and untrusted PHP/JSON surfaces (fail-closed on import and save).

These layers complement each other but are not interchangeable. Audit should not be flooded with every read request, and request logs must not replace domain audit.

### Security baseline checklist (operator)

| Area | Where to verify |
|------|-----------------|
| RBAC + 2FA for staff | Settings → Security, Account security |
| API keys / webhooks secrets | Platform → API keys / Webhooks; requires `APP_KEY` / pepper in `.env` |
| Code policy for extensions | Settings → Code policy; scan runs on every plugin/theme import |
| Upload + content sanitization | Settings → Upload security, Content security |
| Firewall + outbound URL guard | Platform → Firewall; SSRF guard on configurable URLs |
| Backups + scheduler cron | Platform → Backups, Scheduler; host cron required |

## 19. Code Editor, Developer Mode, and extensions

These are high-risk capabilities. Use them on staging, with a time-limited unlock and a backup. Saving in Code Editor does not automatically build, reload, activate a plugin, or deploy.

- [Code Editor](CODE_EDITOR.md)
- [Developer Mode](DEVELOPER_MODE.md)
- [Plugins](PLUGINS.md)
- [Themes](THEMES.md)

## 20. Maintenance, privacy, and analytics

Maintenance/Coming Soon mode should allow staff bypass only for authorized accounts and must not expose drafts accidentally. Newsletter and contact data are subject to privacy and unsubscribe rules.

Analytics is a derived operations layer. Disabling or losing analytics data must not damage content. Cookie consent should respect categories and let visitors change their choice.

## 21. Routine checklist

**Daily:** critical logs, firewall jails, failed jobs, storage capacity.  
**Weekly:** backup report, inactive accounts, pending comments, scheduler health.  
**Before release:** backup + restore test, gate, changelog, config diff, smoke test.  
**After incident:** preserve evidence, rotate compromised secrets, document timeline, and verify recovery.

## 22. Related documents

- [First steps](FIRST_STEPS.md)
- [Content editor](CONTENT_EDITOR.md)
- [Feature gallery](GALLERY.md)
- [Permissions](ACCESS_CONTROL.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
