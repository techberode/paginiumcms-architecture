# Release `v2.1.0-beta.94` — It.58 composer wave + public UX

> **Date:** 2026-10-05  
> **Tag:** `v2.1.0-beta.94`  
> **Previous:** [`v2.1.0-beta.93`](../../CHANGELOG.md#release-2-1-0-beta-93)

---

## One-line summary

Closes the **It.58 / 58f-i** landing composer wave shipped since beta.89: React shortcode islands, unified media lightbox (58f-i-g/h), section-band and pricing/stats motion, editorial workflow, custom 404/500 pages, and a fix for admin **live preview** stuck on “Rendering…”.

**Not in this tag:** inline `[media-gallery]` (**58f-i-i**), video lightbox slides (**58f-i-j**) — see [GALLERY_LIGHTBOX_PLANNED.md](architecture/GALLERY_LIGHTBOX_PLANNED.md).

Handoff: [IT_58_COMPOSER_HANDOFF.md](architecture/IT_58_COMPOSER_HANDOFF.md).

---

## Operator notes

### After deploy

1. Rebuild and serve the frontend (`npm run build:prod` or your deploy script).
2. Open **Shortcodes** admin once (or any editor preview) so `seedMissingBundled()` upgrades **feature-gallery v2**, **section-band v2**, **pricing-table/plan v2**, **stats-row v2**, **cta-banner v2**, **landing-hero v3**, **showcase-hero v3**.
3. **Custom 404:** publish a page (e.g. slug `404`), then **Settings → Layout → Chybové stránky** set **notFoundPageSlug** (or `layout.notFoundPageSlug` in settings JSON).
4. If **Settings → Appearance** PUT returns **403 CSRF**, hard-refresh or re-login so the SPA fetches a fresh CSRF token.

### Security

- `league/commonmark` 2.10.3 (GHSA-97jj-33gv-5xf9, GHSA-3q6v-r5mr-hxv8).
- Internal control audit notes remain in gitignored `SECURITY_ISSUES.md`; public register [ISSUES.md](../ISSUES.md).

---

## Deploy

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.94 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.94`.

---

## Test plan (smoke)

- [ ] Page/article **live preview** completes (shortcodes, snippets, outline) — not infinite spinner ([ISS-194](../ISSUES.md#iss-194)).
- [ ] Public **prose image** lightbox + **feature gallery** modal (thumbnails, counter).
- [ ] `[section-band]` / `[pricing-table billing-toggle="monthly-yearly"]` / `[stats-row animate="count-up"]` on a test landing page.
- [ ] Unknown public slug shows **custom 404 page** when slug configured.
- [ ] YouTube/Vimeo `:::embed` visible on public site ([ISS-193](../ISSUES.md#iss-193)).
- [ ] `./scripts/iteration-gate.sh` green on the tag checkout.
