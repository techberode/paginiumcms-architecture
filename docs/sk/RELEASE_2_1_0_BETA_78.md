# Release `v2.1.0-beta.78` — Support Kanban + domain IMAP

> **Dátum:** 15. september 2026  
> **Tag:** `v2.1.0-beta.78`  
> **Typ:** It.93l / It.93m — Kanban supportu a pošta na doméne webu

Kanónické poznámky (EN): [../en/RELEASE_2_1_0_BETA_78.md](../en/RELEASE_2_1_0_BETA_78.md)

---

## Zhrnutie

Operátori dostanú **Kanban** (`/kanban`) na našich ticketoch a **IMAP schránku** (`/mail`) len pre `@doména-webu`, s odosielaním cez SMTP, extra účtami a plávajúcim tlačidlom Nová správa.

**Zostáva:** **93l-2** interné poznámky / SLA.

---

## Deploy

```bash
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.78 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

---

## Related

[ITERATION_93.md](ITERATION_93.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
