# Release `v2.1.0-beta.69` — Theme Studio, incremental backups

> **Date:** 2026-09-10  
> **Tag:** `v2.1.0-beta.69`  
> **Type:** It.88 Theme Studio (88a–g); backup scope + incremental snapshots

---

## One-line summary

Ships the **Theme Studio** (Monaco HTML/CSS/JS/`theme.json`, the same untrusted policy as ZIP import, sandboxed preview, normalize-from-paste, persist, PNG thumbnail, slot mapping) and **selectable backup scope** with rsync-style incremental snapshots.

---

## What shipped

| Area | Change |
|------|--------|
| **Theme Studio** | Build → Themes → Edit / New; validate, preview, normalize, save, thumbnail, slots |
| **Policy** | Fail-closed: hostile HTML/CSS/JS never writes; preview iframe `sandbox=""` |
| **Persist** | `POST /api/admin/themes/save` (`themes:edit`); activate stays on existing Themes API |
| **Thumbnail** | PNG `preview.png` upload only — capture from the sandbox iframe is blocked |
| **Backups** | Scope picker + incremental ZIP (manifest + `deletes.json`) |

Public React shells are still the bundled `clean-journal` / `terminal-breach` packages. Studio HTML lives under `backend/resources/views/themes/{id}/`.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.69 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.69 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

Post-deploy:

```bash
curl -fsS https://paginiumcms.com/api/health
```

Confirm `version` is `2.1.0-beta.69`. In admin → Build → Themes → **New**, Save should write a package when policy is clean.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green before tag
- [ ] Themes → New → Save (valid `theme.json` id) creates the package and redirects to edit
- [ ] Hostile `<script>` in layout HTML → save 422, no files written
- [ ] Preview iframe has empty `sandbox` (no scripts, no same-origin)
- [ ] PNG thumbnail upload works; SVG/JPEG rejected
- [ ] Platform → Backups: scoped run and incremental mode still create a ZIP

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-69)
- [ITERATION_88.md](ITERATION_88.md)
- [THEMES.md](user/THEMES.md)
- [BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md)
- [DEPLOY.md](../deploy/DEPLOY.md)
