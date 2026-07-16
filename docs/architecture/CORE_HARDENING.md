# Core hardening (Iteration 20)

Produkčná bezpečnosť a prevádzka jadra PaginiumCMS (release 2.0.8).

## RBAC na mutáciách

| Endpoint | Oprávnenie | Role s prístupom |
|----------|------------|------------------|
| `POST /api/pages`, `POST /api/articles` | `content:create` | EDITOR, ADMIN, SUPER_ADMIN |
| `PUT/PATCH /api/pages|articles/*` | `content:edit` | EDITOR, ADMIN, SUPER_ADMIN |
| `DELETE /api/pages|articles/*` | `content:delete` | EDITOR, ADMIN, SUPER_ADMIN |
| `GET /api/media` | rola | EDITOR, ADMIN, SUPER_ADMIN |
| `POST/PATCH /api/media/*` | `media:upload` | EDITOR, ADMIN, SUPER_ADMIN |
| `DELETE /api/media/*` | `media:delete` | EDITOR, ADMIN, SUPER_ADMIN |

Rola **USER** má len `content:view` – zápis vracia **403**.

ADMIN má `content:manage` / `media:manage`, čo pokrýva všetky akcie v doméne.

## Servovanie médií

- URL v médiách: `/storage/app/content/media/{súbor}`
- Backend route: `GET /storage/{path}` → `backend/storage/{path}`
- Dev: Vite proxy `/storage` → `localhost:8080`
- Produkcia: nginx alias na `backend/storage/` alebo rovnaká Slim route

## Režim údržby

Nastavenie `general.maintenanceMode = true`:

- Verejné API (`/api/pages`, `/api/search`, …) → **503** + `{ maintenance: true }`
- Výnimky: `/api/admin/*`, `/api/auth/*`, `/api/health`, `/api/settings/public`, `/storage/*`
- Prihlásený EDITOR/ADMIN/SUPER_ADMIN môže naďalej volať API (náhľad, editácia)

## Registrácia a komentáre

- `general.allowRegistration = false` → `POST /api/auth/register` → **403**
- `comments.allowGuestComments = false` → neauth `POST /api/comments` → **403**

## Session fixation

`SessionManager::setUser()` volá `session_regenerate_id(true)` pri každom prihlásení.

## Trash (soft-delete)

Pri `FileWriter::delete(..., moveToTrash: true)`:

1. Súbor sa presunie do `content/trash/{timestamp}_{name}`
2. Sidecar `{timestamp}_{name}.meta.json` obsahuje `id`, `originalPath`, `deletedAt`

API:

- `GET /api/admin/trash` – zoznam položiek
- `POST /api/admin/trash/{id}/restore` – obnova + rebuild indexu

## Backup cron

```bash
# Každú hodinu (príklad crontab)
0 * * * * cd /path/to/project && php backend/bin/console backup:run-schedule
```

Plán sa ukladá cez existujúce `BackupManager::scheduleBackup()` do `storage/backups/schedule.json`.

## Frontend

| Funkcia | Cesta / komponent |
|---------|-------------------|
| Náhľad draftu | `/preview/:slug` |
| Staff-only admin | `AdminRoleGuard` |
| Titulok stránky | `PublicSiteLayout` → `document.title` |
| Verzie v editore | `VersionHistory` v `MarkdownEditor` |
| Dev logy | `/developer/logs` |
