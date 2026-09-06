# Release `v2.1.0-beta.67` — Media optimization and avatar normalization

> **Date:** 2026-09-06  
> **Tag:** `v2.1.0-beta.67`  
> **Type:** Media library UX, image re-encode/resize (GD), profile avatars

---

## One-line summary

Manual **image optimization** in the Media library (quick card action + metadata modal with preview-before-save), proportional **resize presets**, and server-side **avatar normalization** (max 512×512, 512 KB) for profile uploads and media picks.

---

## What shipped

| Area | Change |
|------|--------|
| **Quick optimize** | `POST /api/media/{path}/optimize` — re-encode JPEG/PNG/WebP via GD; ⚡ on media card |
| **Image info** | `GET /api/media/{path}/image-info` — size, resolution, MIME, upload date in metadata modal |
| **Resize + preview** | Modal: proportional width/height, presets 1920/1280/1080/960 |
| **Preview before save** | `POST .../optimize/preview` → side-by-side + estimated bytes; `POST .../optimize/apply` commits with token |
| **Preview serve** | `GET /api/media/optimize-preview/{token}` — short-lived preview image (~15 min) |
| **Avatars** | `AvatarImageProcessor` downscales/re-encodes large uploads; FE accepts up to 2 MB, server normalizes |

---

## Requirements

| Requirement | Notes |
|-------------|-------|
| **PHP GD** | Required for optimize and avatar downscale; JPEG/PNG/WebP support must match format |
| **Permissions** | Mutating media routes use `AuthMiddleware` + `media:write` (or equivalent module permission) |
| **Already optimal** | API returns 400 `optimize_no_reduction` when re-encode would not shrink the file |

---

## API additions

| Method | Path | Auth |
|--------|------|------|
| `GET` | `/api/media/{path}/image-info` | session + media read |
| `POST` | `/api/media/{path}/optimize` | session + media write |
| `POST` | `/api/media/{path}/optimize/preview` | session + media write |
| `POST` | `/api/media/{path}/optimize/apply` | session + media write |
| `GET` | `/api/media/optimize-preview/{token}` | session (preview token) |

Preview/apply body (optional): `{ "targetWidth": 1920, "targetHeight": 1080, "quality": 85 }` — dimensions scale proportionally when one side is set.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.67 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.67 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

Post-deploy:

```bash
curl -fsS https://paginiumcms.com/api/health
# Confirm GD in PHP container if optimize returns 400 with optimize_gd_* messages
```

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green before tag
- [ ] Admin → Media → raster image → metadata modal shows dimensions and file size
- [ ] Preview optimize → side-by-side → Apply saves smaller file
- [ ] Quick ⚡ on card works for compressible JPEG/PNG/WebP
- [ ] Profile avatar upload >512 px downscales; public avatar URL serves normalized file
- [ ] 400 with clear message when image already optimally compressed

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-67)
- [ADMIN_GUIDE.md](user/ADMIN_GUIDE.md) §6 Media
- [DEPLOY.md](../deploy/DEPLOY.md)
