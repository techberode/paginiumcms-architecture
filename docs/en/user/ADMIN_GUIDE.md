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

## 5. Articles

Articles use the same editor core and can additionally contain excerpt, tags, featured image, publication time, and comment policy. Scheduled publication requires a functioning scheduler/worker for the concrete release.

Use bulk actions only after checking filters and item counts. With soft delete, inspect trash and retention before permanent removal.

## 6. Media

Media Manager handles uploads, metadata, folders, and asset selection for content or branding.

An administrator checks:

- allowed MIME type and actual file content,
- file-size limit and disk capacity,
- alt/title metadata,
- public versus internal path,
- references before deletion,
- proxy/storage configuration when a file returns 404.

A future local/S3 driver from It.72 does not change the rule that authoritative metadata remains under CMS control and migration must be verifiable.

## 7. Navigation

Navigation defines the public link tree, order, parent relationships, and targets. Export or back up configuration before a large change. Test desktop, mobile, and keyboard navigation.

## 8. Comments and messages

**Comments** support moderation under global and per-content policy. **Messages** form the contact inbox.

- do not export personal data without a valid reason,
- combine rate limits, WAF, and moderation for spam,
- an urgent label does not replace a notification channel,
- deletion may be subject to retention policy.

## 9. Settings

Settings are divided into groups. Typical areas:

| Area | Examples |
|---|---|
| Site | name, URL, language, timezone, branding |
| Content/SEO | editor, pagination, metadata, feeds |
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

## 12. Scheduler and jobs

The scheduler UI displays defined jobs; actual execution depends on cron/worker processes. Monitor last run, next run, duration, lock, and last error.

Do not trigger a long job repeatedly just because the UI appears unresponsive. Check workers and logs first to avoid duplicate mail, backups, or Git publication.

## 13. Backups and restore

A backup must cover authoritative content, settings, required keys, and namespaced extension data. Cache and index are rebuildable and should not be the sole recovery source.

### Automatic scheduled backups

Scheduled backups do **not** run from the browser alone. Enable all of the following:

1. **Settings → Job scheduler** — master switch on.
2. **Platform → Scheduler** — enable the `backup-scheduled` job (default cron: daily at 02:00).
3. **Host cron** — run `php backend/bin/console scheduler:run` every minute (see `docs/deploy/CRON.md`).
4. **Platform → Backups** — open **Automatic backups**, choose interval and retention, save.

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

## 16. Translation and localization

Admin UI translation management is not the same as a multilingual content document. The target It.73/76/77 localization branch uses proposal and diff; translated content must not be published automatically without a separate approval.

## 17. Firewall, logs, and audit

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

## 18. Code Editor, Developer Mode, and extensions

These are high-risk capabilities. Use them on staging, with a time-limited unlock and a backup. Saving in Code Editor does not automatically build, reload, activate a plugin, or deploy.

- [Code Editor](CODE_EDITOR.md)
- [Developer Mode](DEVELOPER_MODE.md)
- [Plugins](PLUGINS.md)
- [Themes](THEMES.md)

## 19. Maintenance, privacy, and analytics

Maintenance/Coming Soon mode should allow staff bypass only for authorized accounts and must not expose drafts accidentally. Newsletter and contact data are subject to privacy and unsubscribe rules.

Analytics is a derived operations layer. Disabling or losing analytics data must not damage content. Cookie consent should respect categories and let visitors change their choice.

## 20. Routine checklist

**Daily:** critical logs, firewall jails, failed jobs, storage capacity.  
**Weekly:** backup report, inactive accounts, pending comments, scheduler health.  
**Before release:** backup + restore test, gate, changelog, config diff, smoke test.  
**After incident:** preserve evidence, rotate compromised secrets, document timeline, and verify recovery.

## 21. Related documents

- [First steps](FIRST_STEPS.md)
- [Content editor](CONTENT_EDITOR.md)
- [Permissions](ACCESS_CONTROL.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
