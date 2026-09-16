# Release `v2.1.0-beta.77` — Admin chrome + daily apps

> **Date:** 2026-09-15  
> **Tag:** `v2.1.0-beta.77`  
> **Type:** It.93 Wave 1–3 — admin chrome, teams, account, events, time tracker, widgets, catalog menu

---

## One-line summary

The admin kitchen gets chrome (tokens, top vs side nav, Apply/Save dock) plus daily apps on **our** flat-file data: **teams**, **account**, **events**, **time tracker**, **widgets**, and a **catalog side menu** that never collides with the header.

---

## What shipped

| Area | Change |
|------|--------|
| **Admin chrome** | Light sidebar / optional top dropdown, 8 chrome colors + gradient, light/dark toggle, opaque topbar sibling of the scroll pane, floating Apply/Save |
| **Widgets** | `[widget type="kpi" … /]` catalog + custom HTML definitions under `data/widgets/definitions/` (CodePolicy) |
| **Teams** | `data/teams/{id}.json` (`team@1`) — editorial / support / ops / custom; ADMIN+ at `/teams` |
| **Account** | `/account` tabs; `PUT /api/auth/me`; public opt-in staff cards `GET /api/public/staff`; content share bar |
| **Events** | `data/events/{id}.json` (`site-event@1`) — list + month calendar at `/events` |
| **Time tracker** | `data/time-entries/{id}.json` (`time-entry@1`) — one running timer per user; coming-soon countdown on a page/article |
| **Catalog menu** | `data/secondary-navigation.json` — multi-level side menu; header and side never share the same tree; hamburger matches the desktop breakpoint |
| **Responsive** | Sticky public header publishes `--pg-public-header-height`; grids step down when the side column is on |

**Not in this release:** **93l** support desk, **93m** domain IMAP inbox. Isolated-origin widgets stay cancelled ([ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md)).

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.77 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.77 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Admin Settings → Admin UI: switch side vs top nav; pick a chrome color; toggle light/dark — no public theme leak on `/categories` or `/gallery`
- [ ] `/teams` create a Support team; `/account` edit public card + timezone
- [ ] `/events` create a published event; `/time-tracker` start a timer on a page
- [ ] Settings → Navigation: Header vs Catalog tabs; enable catalog, place left/right — public header and side do not duplicate links; hamburger hides when desktop nav shows
- [ ] Insert `[widget type="kpi" … /]` on a page; public page expands it
- [ ] `./scripts/iteration-gate.sh` green on checkout

---

## Related

[ITERATION_93.md](ITERATION_93.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
