#!/usr/bin/env bash
# Publish GitHub releases for v2.0.43 and v2.0.44 (requires: gh auth login)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if ! command -v gh >/dev/null 2>&1; then
  echo "Install GitHub CLI: https://cli.github.com/" >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Run once: gh auth login" >&2
  exit 1
fi

gh release create v2.0.43 \
  --title "2.0.43 — It.55: Tiptap JSON storage + editor image upload" \
  --notes-file - <<'EOF'
## Summary

Iteration 55 persists WYSIWYG content as structured Tiptap JSON, renders sanitized HTML on the backend, and wires image paste/drop/upload into the existing media DAM. Includes ISS-042 login session retry fix.

## Highlights

- **`contentFormat: tiptap_json`** — save/load round-trip with cached `html`
- **`TiptapHtmlRenderer` + `ContentBodyRenderer`** — public HTML from JSON
- **Profile-aware validation** — Tiptap node walk (It.54 profiles)
- **Editor upload** — paste, drop, file picker → `/api/media/upload`
- **ISS-042** — `probeSessionWithRetry` after login

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] WYSIWYG save → reload → same content
- [ ] Image upload from editor → visible on public page
- [ ] Markdown-only articles unchanged
- [ ] Single login on localhost:3025

## Docs

- [ITERATION_55.md](docs/ITERATION_55.md)
EOF

gh release create v2.0.44 \
  --title "2.0.44 — It.18 i18n + translation editor, It.19a admin UX" \
  --latest \
  --notes-file - <<'EOF'
## Summary

Iteration 18 migrates admin UI to modular i18n (`useI18n()`) and adds a translation editor for backend lang files and frontend i18n modules. Iteration 19a delivers grouped admin navigation, settings categories, translation save policy (staging + rejected `.err` copies), and security settings schema groups.

Includes HookManager DI hotfix (146 PHPUnit errors) and Vitest `TestI18nProvider` harness fix.

## Highlights

- **i18n modules:** admin, list, content, settings, translations
- **Translation editor:** `/translations` + `/api/admin/translations/*` (Admin + 2FA)
- **Save policy:** staging → validate → promote; sequential policy toasts
- **Settings UX:** System / Site / Media / Security category menu
- **Schema:** `contentSecurity`, `uploadSecurity` groups (UI; runtime wiring in It.19b)
- **Admin nav:** 6 collapsible sidebar sections + header collapse toggle

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Switch admin language SK ↔ EN — sidebar and lists update
- [ ] Translation editor — valid save promotes; invalid save leaves original + `.err` copy
- [ ] Settings categories and URL `?category=&group=` deep links
- [ ] Sidebar collapse persists after reload

## Docs

- [ITERATION_18.md](docs/ITERATION_18.md)
- [ITERATION_19.md](docs/ITERATION_19.md)
EOF

echo "Done. See: https://github.com/techberode/paginiumcms-architecture/releases"
