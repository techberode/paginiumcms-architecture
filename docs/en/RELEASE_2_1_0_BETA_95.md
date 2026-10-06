# Release `v2.1.0-beta.95` — Media gallery shortcode + unified lightbox

> **Date:** 2026-10-06  
> **Tag:** `v2.1.0-beta.95`  
> **Previous:** [`v2.1.0-beta.94`](../../CHANGELOG.md#release-2-1-0-beta-94)

---

## One-line summary

Ships **It.58f-i-i/j/k**: `[media-gallery]` DAM block, **video + embed** slides in `PaginiumMediaGallery`, feature gallery grid/slider parity with shareable `?slide=` URLs, and user docs ([SHORTCODE_COOKBOOK.md](user/SHORTCODE_COOKBOOK.md), SK [SHORTCODES_A_WIDGETY.md](../sk/user/SHORTCODES_A_WIDGETY.md)).

Lightbox wave complete: [GALLERY_LIGHTBOX_PLANNED.md](architecture/GALLERY_LIGHTBOX_PLANNED.md).

---

## Operator notes

### After deploy

1. Rebuild frontend (`npm run build:prod` or deploy script).
2. Admin → **Shortcodes** — open once (`seedMissingBundled` for `media-gallery` v1).
3. Re-save landing pages that use `[media-gallery]` or gallery blocks.
4. Purge content cache if HTML is stale.

### Docs

- [SHORTCODE_COOKBOOK.md](user/SHORTCODE_COOKBOOK.md) — all bundled shortcodes/widgets with examples  
- [GALLERY.md](user/GALLERY.md) — `feature-gallery` vs `media-gallery`, deep links  
- [MEDIA_IN_CONTENT.md](user/MEDIA_IN_CONTENT.md) — prose lightbox grouping  

---

## Deploy

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.95 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.95`.

---

## Test plan (smoke)

- [ ] `[media-gallery ids="media/…"]` with images + mp4 on a test page; lightbox plays video.
- [ ] Prose image + DAM video + grouped `data-gallery` in one album.
- [ ] **Feature gallery** grid/slider; `/features?slide=<item-id>` opens correct slide; URL updates when navigating lightbox.
- [ ] `./scripts/iteration-gate.sh` green on tag checkout.
