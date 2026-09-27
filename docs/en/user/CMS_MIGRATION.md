# CMS content migration

Import **pages** and **articles** from common flat-file and export formats into PaginiumCMS storage.

## Admin UI

1. Open **Operations → CMS migration** (`/content-migration`).
2. Choose source CMS (or **Auto-detect** for ZIP archives).
3. Upload export file.
4. Run **Preview import (dry-run)** — no writes.
5. Run **Import now** to persist content.

Requires **content:create** permission (admin + 2FA).

## CLI

From project root:

```bash
php backend/bin/console content:import --file=/path/export.xml --format=wordpress --run
php backend/bin/console content:import --format=grav --path=/grav/user/pages --run
php backend/bin/console content:import --file=/site.zip --run
```

Omit `--run` for dry-run (default).

## Supported sources

| CMS | Input | Notes |
|-----|--------|--------|
| WordPress | WXR `.xml` | Posts → articles, pages → pages; categories/tags merged into `tags` |
| Grav | `user/pages/**/*.md` or ZIP | Blog items with `date` → articles |
| Jekyll | Site root with `_posts/` | Dated posts → articles |
| Hugo | `content/` tree or ZIP | `posts/` → articles |
| Ghost | JSON export | Posts/pages from `posts` array |
| Paginium | JSON from `content:export` | Full control bundle |

## Limits (phase 1)

- Embedded **media URLs** in HTML/Markdown are **not** downloaded; re-link or import media separately.
- Slug collisions rename to `import-{slug}` (see import log).
- No automatic navigation/menu migration.
