# Iteration 64 — Footer social links

**Status:** ✅ Shipped in `v2.1.0-beta.19`
**Priority:** 🟡 Medium  
**Related:** [ADMIN_GUIDE.md](../user/ADMIN_GUIDE.md) · marketing settings group

## Goal

Editable footer social / project links with platform icons — for open-source promotion and testing, without hardcoding URLs in the theme.

## Scope

| Area | Description |
|------|-------------|
| **Admin** | Settings → Site → **Marketing & social** — visual editor (platform, URL, label, enabled, reorder) |
| **Public footer** | Icon row above copyright; Lucide icons per platform |
| **API** | `GET /api/settings/public` → `social.enabled`, `social.links[]` |
| **Defaults** | PaginiumCMS GitHub repo when `socialLinksJson` is empty |

## Platforms

`github`, `gitlab`, `twitter`, `facebook`, `instagram`, `linkedin`, `youtube`, `mastodon`, `discord`, `website`, `email`, `rss`

## Backend

- `SettingsSchema` — group `marketing`: `socialLinksEnabled`, `socialLinksJson`
- `SocialLinksNormalizer` — validate URLs, allowlist platforms, max 12 links
- `SettingsController` — normalize on `PUT /api/admin/settings/marketing`; expose `social` in public settings

## Frontend

- `SocialLinksSettingsPanel.tsx` — admin editor
- `FooterSocialLinks.tsx` — public icon row
- `utils/socialLinkIcons.tsx` — platform → Lucide icon mapping

## Acceptance

- [x] Admin can add/edit/remove/reorder social links with icons
- [x] Footer shows enabled links when master toggle is on
- [x] Invalid platform/URL rejected with 422
- [x] PHPUnit + Vitest coverage
- [x] Docs: CHANGELOG, ADMIN_GUIDE

## Smoke test

1. Admin → **Settings** → category **Site** → tab **Marketing & social**.
2. Enable **Show social links in footer**; add GitHub URL; save.
3. Open public site footer — GitHub icon visible; click opens repo.
4. Disable one link or master toggle — icon disappears after reload settings.
5. `curl -s http://localhost:8080/api/settings/public | jq '.data.social'`
