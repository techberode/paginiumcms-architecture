# Release `v2.1.0-beta.96` — Experience B1 + B2 motion

> **Date:** 2026-10-06  
> **Tag:** `v2.1.0-beta.96`  
> **Previous:** [`v2.1.0-beta.95`](../../CHANGELOG.md#release-2-1-0-beta-95)

---

## One-line summary

Ships **Experience Phase B1–B2**: `[visual-frame]` scroll motion presets in the insert modal, extended **`[section-band]`** `reveal` / `hover-effect` enums (seeder **v3**), planning docs (**It.98**, product growth map), and SK publication drafts with links to GitHub docs.

Gallery/lightbox completion remains in **beta.95** — [RELEASE_2_1_0_BETA_95.md](RELEASE_2_1_0_BETA_95.md).

---

## Operator notes

### After deploy

1. Rebuild frontend (`npm run build:prod` or deploy script).
2. Admin → **Shortcodes** — open once (`seedMissingBundled` for `visual-frame` v2, `section-band` v3).
3. Re-save landing pages that use section bands or visual-frame wrappers if catalog attrs were stale.
4. Purge content cache if public HTML is old.

### Docs

- [SHORTCODE_COOKBOOK.md](user/SHORTCODE_COOKBOOK.md) — section-band + visual-frame motion  
- [PUBLIC_EXPERIENCE_ROADMAP.md](architecture/PUBLIC_EXPERIENCE_ROADMAP.md) — Phase B status  
- [PRODUCT_GROWTH_PROPOSALS.md](architecture/PRODUCT_GROWTH_PROPOSALS.md) — unshipped A/B, TOC, multi-site  
- [ITERATION_98.md](ITERATION_98.md) — workspace + safe CLI (planned)  
- [DOCS_PORTAL_PLANNED.md](developer/DOCS_PORTAL_PLANNED.md) — future in-CMS Markdown docs module  
- SK drafts (tracked): [docs/sk/marketing/](../sk/marketing/README.md)

---

## Deploy

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.96 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.96`.

---

## Test plan (smoke)

- [ ] `[section-band reveal="scale-in" hover-effect="tilt-3d"]` with nested `[feature-card]` — scroll into view + hover tilt.
- [ ] Insert modal **Scroll effect** on a block wrapped in `[visual-frame motion="stagger"]`.
- [ ] `prefers-reduced-motion: reduce` — no transform animation on band/cards.
- [ ] `./scripts/iteration-gate.sh` green on tag checkout.
