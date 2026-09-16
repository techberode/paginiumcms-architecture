# Release `v2.1.0-beta.78` — Support Kanban + domain IMAP mail

> **Date:** 2026-09-15  
> **Tag:** `v2.1.0-beta.78`  
> **Type:** It.93l / It.93m — support desk Kanban and domain mail client

---

## One-line summary

Operators get a Support **Kanban** board on our tickets and a **domain IMAP** inbox (own `@site` mailboxes only) with SMTP send/reply, extra accounts, and a floating compose button.

---

## What shipped

| Area | Change |
|------|--------|
| **Kanban** | `/kanban` — columns/labels in `data/support-board.json`, tickets in `data/support-tickets/{id}.json`. Assignee pool is the Support team. Permission `support-ticket:manage`. |
| **IMAP inbox** | `/mail` — folders, sandboxed HTML, tags/star/read, local hide (IMAP intact). Host must be on the CMS domain; Gmail blocked. Passwords encrypted in `data/mail-secrets/`. |
| **SMTP send** | Compose + reply via existing SMTP settings. From is the working mailbox. Extra `@site` mailboxes can be added and switched. Floating **New message** stays on screen while scrolling. |
| **Settings** | IMAP group under Settings → System (next to SMTP). |

**Remainder:** **93l-2** canned replies / SLA notes. Isolated-origin widgets stay cancelled.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.78 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.78 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] `/kanban` create a column and a ticket assigned to a Support-team member
- [ ] Settings → Email / IMAP: enable host on the CMS domain; save mailbox password; list INBOX
- [ ] Open a message (HTML in sandbox iframe); reply and send via SMTP
- [ ] Add a second `@site` mailbox and switch; **New message** stays visible while scrolling
- [ ] Gmail / off-domain mailbox is rejected

---

## Related

[ITERATION_93.md](ITERATION_93.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
