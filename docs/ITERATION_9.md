# Iteration 9 – Prototype Backend Port

**Status:** Complete  
**Version:** 2.0.5 (pending tag)

## Summary

Mapped `prototype/backend/` script APIs to production Slim modules. Most prototype endpoints were mocks or already superseded; this iteration ports the **missing real features** into flat-file services under `/api/*`.

## Prototype → Current mapping

| Prototype (`/backend/v1/...`) | Current API | Status |
|---|---|---|
| `navigation.php` | `GET /api/navigation`, `PUT /api/admin/navigation` | ✅ Ported |
| `comments.php` | `GET/POST /api/comments`, admin `/api/admin/comments/*` | ✅ Ported |
| `contact.php` | `POST /api/contact` | ✅ Ported |
| `messages.php` | `GET/PATCH/DELETE /api/admin/messages/*` | ✅ Ported |
| `github-sync.php` | `GET/POST/PUT /api/admin/github/*` | ✅ Ported (REST via `GitHubService`) |
| `pages`, `blog`, `media`, `auth`, `users`, `settings`, `dashboard`, `analytics` | Existing `/api/*` | Already implemented |
| `debug-toast(s)`, `index.php` mocks, `smtp.php` secrets | — | Skipped (insecure / obsolete) |

## New backend modules

| Module | Storage | Routes |
|---|---|---|
| Navigation | `data/navigation.json` | Public read + admin update |
| Comments | `data/comments.json` | Public submit/list approved + admin moderate |
| Messages | `data/messages/*.json` | Public contact form + admin inbox |
| GitHub | env + `GitHubService` | Admin status/export/import/sync |

## Settings

Added `comments` group to `SettingsSchema`: `enabled`, `requireApproval`, `allowGuestComments`, `maxLength`.

## Environment

Optional GitHub sync variables (see `.env.example`):

- `GITHUB_ENABLED`, `GITHUB_TOKEN`, `GITHUB_REPO`, `GITHUB_BRANCH`, `GITHUB_AUTO_SYNC`, `GITHUB_CONTENT_PATH`

## Tests

- Repository tests: Navigation, Comments, Messages
- Controller tests: Navigation, Comments, Contact, GitHub

## Next

- Wire FE: ~~NavigationManager, CommentsManager, MessagesViewer, ContactForm~~ ✅ Done in 2.0.5
- Replace `PublicSiteContext` nav builder with `/api/navigation` ✅
