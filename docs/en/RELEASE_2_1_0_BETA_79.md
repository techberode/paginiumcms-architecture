# Release `v2.1.0-beta.79` — Mail polish, 58f blocks, editor workspace

> **Date:** 2026-09-17  
> **Tag:** `v2.1.0-beta.79`  
> **Type:** It.93m-5 mail UX · It.58f visual blocks · editor workspace · encryption baseline

---

## One-line summary

Domain mail gets labels, local-trash empty, compose polish, and signatures with inline avatars; pages gain 58f hero/gallery rendering; editors can use fullscreen workspace; secrets at rest fail closed without `APP_KEY`.

---

## What shipped

| Area | Change |
|------|--------|
| **Mail labels** | Sidebar catalog (colors, edit/delete), apply/remove on messages, bulk label remove. |
| **Local trash** | **Empty local trash** dismisses all hidden messages permanently in this client (IMAP unchanged). |
| **Compose** | Draft autosave, multiple recipients, manual **Refresh**, optional skip IMAP Sent append (`imap.appendSentOnSend`). |
| **Signatures** | Six templates; card avatar as CID in outbound MIME. |
| **58f** | `landing-hero` + `feature-gallery` server renderers; outline/layout polish; [GALLERY.md](user/GALLERY.md). |
| **Editor** | Fullscreen **workspace** (Settings → Editor + toggle in UI). |
| **Lists** | Search field **×** clears query (all admin lists using `AdminListToolbar`). |

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.79 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.79 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] `/mail` — create label, color it, assign/remove on a message, empty **Local trash** after hiding a message
- [ ] Compose with two recipients; send; verify signature + optional Sent append setting
- [ ] Page editor — outline mode, hero/gallery block preview; toggle fullscreen workspace
- [ ] Without `APP_KEY`, saving mailbox password shows encryption-unavailable (no plaintext write)
- [ ] Admin list search — type query, click **×**, field clears

---

## Related

[ITERATION_93.md](ITERATION_93.md) · [ITERATION_58f.md](ITERATION_58f.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
