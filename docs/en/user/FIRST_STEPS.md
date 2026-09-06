---
title: First steps
description: First login, security baseline, and creating initial content
icon: material/rocket-launch
---

# First steps after installation

> Prerequisite: the instance passed [installation](INSTALLATION.md), `/api/health` responds, and you have a bootstrap staff account.

## 1. First login

**Browser setup (recommended):** after [installation](INSTALLATION.md), open `/setup` on a fresh instance. Complete the wizard steps (**Server → Administrator → Site → Infrastructure**), fix any hard preflight failures using the displayed commands, then finish — you are redirected to **`/login`** and must sign in with the administrator account you just created.

**CLI bootstrap:** if you used `first-run.sh` / `bootstrap-admin.php` instead:

1. Open `/login` on the canonical production HTTPS URL.
2. Sign in with the bootstrap account created during first-run.
3. Immediately replace any known or temporary password.
4. Complete 2FA onboarding when required.
5. Store recovery codes away from the server and away from the same password-manager item as the password.

Do not disable 2FA in production merely to make onboarding pass. When QR/TOTP fails, check server time, session cookies, HTTPS proxy headers, and logs.

## 2. Verify the instance identity

Before changing real data, verify:

- hostname and environment banner,
- release/version from an available health/about endpoint or the release artifact,
- whether this is demo, staging, or production,
- whether writes target the expected storage directory,
- whether the first backup exists.

This prevents the classic “I edited production because I thought it was staging” incident. An old admin evergreen. 🙂

## 3. Dashboard and navigation

Visible modules depend on role, permissions, feature flags, and deployment profile. Typical groups are:

| Section | Modules |
|---|---|
| Overview | dashboard, quick actions, status panels |
| Workspace | pages, articles, media, navigation |
| Inbox | comments and contact messages |
| Platform | settings, users, notifications, scheduler, account |
| Security | firewall, logs, audit, security audit |
| Operations | backups, trash, Git integration when configured |
| Development | extensions, blueprints, Code Editor, and Developer Mode according to role |

When a module is missing, first verify permission and environment gating. Do not “fix” the sidebar by unlocking frontend code; the backend must authorize the action too.

## 4. First-day security baseline

- create a second trusted recovery administrator when the operating model requires it,
- enable 2FA for every staff account,
- confirm `APP_DEBUG=false` and HTTPS,
- verify firewall behavior and the real client IP,
- configure log and backup retention,
- remove test users and unused API/extension secrets,
- verify that storage, logs, and backups are not public,
- test restore on an isolated copy at least once.

## 5. Basic site settings

Configure at least:

| Area | Check |
|---|---|
| Identity | name, base URL, language, timezone |
| Branding | logo, favicon, and light/dark fallback |
| SEO | default metadata, indexing, and canonical URL |
| Accounts | registration, password policy, 2FA |
| Email/notifications | provider, encrypted secrets, and send test |
| Logging | minimum severity, request logging, retention |
| Firewall | enabled state, jail profile, whitelist only justified IPs |

Never include secrets in an issue report. Changing secrets may require service reload or a new login according to the concrete contract.

## 6. Create the first page

1. Open **Pages** and create a new item.
2. Enter a title and safe slug.
3. Choose Markdown or WYSIWYG mode.
4. Add content, meta description, and optionally an OG image.
5. Save as `draft` first.
6. Review preview and links.
7. Publish and verify the public URL in an anonymous window.

A draft is not public content. Admin preview may use a staff session, so always test public visibility while signed out.

## 7. Create the first article

An article adds tags, excerpt, featured/OG image, and comments according to settings. After publication verify:

- the card in the blog list,
- article detail,
- image through its public media URL,
- title/meta/OG values,
- feed or sitemap only when supported and enabled by the release.

## 8. Media and alternative text

Upload a test image through Media Manager rather than copying it into storage manually. Verify type, size, preview, and public path. Add meaningful `alt` text; mark decorative images according to UI rules.

External URLs may disclose a visitor IP to a third party. Prefer locally managed assets for branding and content unless an external asset is intentional.

## 9. Navigation

Add the page to navigation, set order and target, and verify desktop and mobile menus. External links opened in a new window must use the secure `rel` behavior implemented by the application.

## 10. Create an editor and test least privilege

1. Create an account with the `EDITOR` role.
2. Sign in through a separate browser profile.
3. Verify that it can edit allowed content.
4. Verify that platform and security actions remain inaccessible.
5. When Path ACL is enabled, test both an allowed and a denied path.

Hiding a button in the frontend is not authorization. A forbidden mutation must fail through a direct API request too.

## 11. Review audit, logs, and backup

After the first changes you should be able to locate:

- login and security events,
- content creation/modification in audit,
- an HTTP request record without secrets,
- firewall incidents only for real scenarios,
- a successfully created backup with integrity verification.

**Restore drill (recommended on dev/staging):** create a backup, soft-delete one article, restore, confirm the article reappears. If restore “succeeds” but content is missing, see [BACKUP_RESTORE.md](../developer/BACKUP_RESTORE.md) (wrong path `content/content/`, legacy ZIP without `blog/`, or stale cache — [ISS-163](../ISSUES.md#iss-163)).

## 12. What is not automatic

Under the Hybrid Engine direction, do not assume that **Save** automatically means:

```text
Git commit/push
translation to another locale
AI approval
purging every cache
frontend build
deployment to a CDN
```

Each step has its own state, permission, and failure mode. Verify availability in release notes.

## 13. Next step

- daily administration: [ADMIN_GUIDE.md](ADMIN_GUIDE.md)
- in-depth editing: [CONTENT_EDITOR.md](CONTENT_EDITOR.md)
- permissions: [ACCESS_CONTROL.md](ACCESS_CONTROL.md)
- firewall and logs: [FIREWALL.md](FIREWALL.md), [LOGGING.md](LOGGING.md)
