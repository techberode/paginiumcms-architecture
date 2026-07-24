# Iteration 56 – Rich navigation menu items

**Status:** ✅ Complete · **2.1.0-beta.5**  
**Wave:** Post-15 Editor & UX  
**Priority:** 🟡 Medium

## Summary

Extend flat-file navigation (`data/navigation.json`) with **descriptions**, **icons or thumbnails**, and **hover preview** (tooltip enlargement). Sizes and proportions configurable in admin Settings.

## Data model (navigation item)

| Field | Type | Description |
|-------|------|-------------|
| `description` | string | Subtitle under menu label (desktop dropdown + mobile) |
| `iconType` | enum | `none` \| `lucide` \| `media` |
| `iconValue` | string | Lucide icon name **or** media path |
| `previewOnHover` | bool | Enable enlarged preview tooltip |
| `previewScale` | float | Hover scale factor (e.g. 1.5, 2.0) — proportional |
| `thumbnailSize` | enum | `sm` \| `md` \| `lg` inline thumbnail |

Settings group **`navigationUi`** (global defaults):

- default preview scale, max tooltip width, enable animations

## Admin UX

- **NavigationManager** — inline fields for description + media picker for icon/thumbnail
- Live preview of dropdown row (label + description + mini thumb)
- Validation: max description length, allowed media MIME for icons

## Public UX (`Navbar`)

- Desktop: dropdown shows description under title; optional 24–32 px thumbnail
- Hover: tooltip with larger image/icon (`previewScale`), respects `prefers-reduced-motion`
- Mobile: description as secondary line (no hover tooltip)

## Backend

- Extend `Navigation` / `NavigationItem` models
- `PUT /api/admin/navigation` validation (depth unchanged — max 3 levels from It.42+)
- Public `GET /api/navigation` includes new fields

## Dependencies

- ⛔ **After [It.15](ITERATION_15.md)** (optional plugin hooks for custom icon packs later)
- ✅ Nested menu (3 levels) — [CONTENT_COMMENTS_NAV.md](CONTENT_COMMENTS_NAV.md)
- ✅ MediaRepository for thumbnail paths

## Acceptance criteria

- [x] CRUD description + media icon on menu item persists in `navigation.json`
- [x] Public navbar renders description; hover preview works on desktop
- [x] Settings control default preview scale (`navigationUi`)
- [x] PHPUnit navigation validation tests updated
- [x] Vitest Navbar dropdown with description + hover mock

## Implementation notes

- `NavigationItem::fromPayload()` — single hydrate path (BE)
- `NavigationRichFieldValidator` — description length, icon type, media path extension
- Legacy `icon` field migrated on read → `iconType` + `iconValue`
- Settings `defaultPreviewScale` stored as int ×10 (15 = 1.5×) for form compatibility
- Lucide icons: dynamic lookup via `lucide-react` `icons` (ISS-085 fix — not hardcoded whitelist)
- Desktop top-level nav shows description via `NavItemContent` (ISS-085)

## Next

→ [Iteration 57](ITERATION_57.md) — auto tags & description generator ✅  
→ [Iteration 60](ITERATION_60.md) — custom editor modules
