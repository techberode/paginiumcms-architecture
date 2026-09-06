# Backup and restore

> **Audience:** operators and developers  
> **Service:** `BackupManager` (`backend/app/Core/Backup/Services/BackupManager.php`)  
> **Admin UI:** Platform → Backups  
> **API:** `/api/admin/backups/*` (auth + `backups:*` permissions)

---

## Overview

PaginiumCMS stores authoritative content under `storage/app/content/`. Admin backups are ZIP archives written to `storage/backups/` with sidecar metadata (`{id}.json`).

A **successful restore** must:

1. Write pages, articles, media, and `data/` back to the correct paths under `storage/app/content/`.
2. Rebuild the content index (`data/index/content.json`) from disk.
3. Purge content list/payload caches so the admin UI and public API reflect restored files immediately.

Restore is **not** a Git rollback and does not revert PHP code or frontend assets.

---

## ZIP layout (current format, ≥ 2.1.0-beta.65)

| ZIP prefix | Source on disk | Notes |
|------------|----------------|-------|
| `content/pages/` | `storage/app/content/pages/` | Markdown/JSON pages |
| `content/blog/` | `storage/app/content/blog/` | Articles |
| `content/media/` | `storage/app/content/media/` | Local media objects |
| `content/data/` | `storage/app/content/data/` | Settings, users, indexes, jobs, … |
| `content/trash/` | `storage/app/content/trash/` | Soft-deleted files + sidecars |
| `content/navigation/` | `storage/app/content/navigation/` | When present |
| `config/` | `storage/app/config/` | Optional when `includes` contains `config` |
| `backup.json` | — | Metadata snapshot at create time |

### Legacy format (pre-fix backups)

Older archives may contain only `data/` at the **ZIP root** (no `content/` tree). Restore still accepts these: `data/` is merged into `storage/app/content/data/`.

**Important:** legacy backups created before the content-tree fix often **do not contain** `pages/`, `blog/`, or `media/`. Restoring them updates settings/indexes but **will not bring back articles or pages**. Create a **new backup** after upgrading to a fixed release before relying on restore for content disaster recovery.

---

## Create backup

**API:** `POST /api/admin/backups` with `{ "name": "…", "includes": ["content", "config", "data"] }` (default includes).

Default `includes: ["content", "config", "data"]` zips the full `content/` subtree plus app config. Scheduled backups use the same manager.

**Integrity:** each backup stores `sha256` in metadata. Use **Verify** in admin or `GET /api/admin/backups/{id}/verify`.

---

## Restore backup

**API:** `POST /api/admin/backups/{id}/restore`

Flow:

1. Extract ZIP to a temp directory (Zip-Slip guarded via `ZipEntryGuard`).
2. Merge `content/` → `storage/app/content/` (file-by-file overwrite).
3. Merge legacy root `data/` → `storage/app/content/data/` when present.
4. Merge `config/` → `storage/app/config/` when present.
5. **Rebuild** content index from disk (`ContentIndexService::rebuild`).
6. **Purge** content caches (`ContentCacheService::purgeAll`).

### Soft delete (trash) scenario

When an article is deleted through the CMS, the file moves to `content/trash/` and the index entry is removed. Restoring a backup taken **before** the delete:

- Writes the article back to `content/blog/{slug}.md`.
- Restores `data/index/content.json` from the archive **and** rebuilds the index after merge (authoritative = files on disk).
- Does **not** automatically remove trash sidecars created after the backup. If the same slug exists in both `blog/` and `trash/`, prefer the live `blog/` file; empty stale trash entries manually if needed.

Always verify restore with a **delete → restore** drill on a dev copy ([ISS-163](../ISSUES.md#iss-163)).

---

## Path contract (FileWriter)

All content writes go through `FileWriter` with paths **relative to** `storage/app/content/` (same base as `FileValidator`):

```text
blog/my-article.md
pages/home.md
data/settings.json
```

Restore must **not** prefix paths with `content/` when calling `FileWriter`. A regression wrote files to `storage/app/content/content/blog/` (double `content/`), which CMS list endpoints do not read ([ISS-163](../ISSUES.md#iss-163)).

---

## Troubleshooting

| Symptom | Likely cause | Action |
|---------|--------------|--------|
| Restore “succeeds” but articles/pages missing | Legacy ZIP without `content/blog` or `content/pages` | Create a new backup on a fixed release; re-import prod content if needed |
| File on disk under `content/content/` | Restore path bug (fixed ISS-163) | Upgrade, remove orphan `storage/app/content/content/`, run restore again |
| Article file exists but admin list empty | Stale content cache (fixed ISS-163) | Upgrade; or purge cache via admin cache tools / restart PHP workers |
| Import fails with 403 | Missing CSRF on upload (fixed ISS-163) | Use current frontend; ensure `X-CSRF-TOKEN` on `POST /api/admin/backups/import` |
| Download dialog stuck | Browser blob revoke race (fixed ISS-163) | Use current frontend (direct download link) |
| Prod backup on dev shows no content | Expected if ZIP is legacy data-only | Same as row 1 |

### Clean up orphan double-`content/` tree (after bad restore)

Only after confirming a successful restore to correct paths:

```bash
# Inspect first — must NOT be the live content tree
ls backend/storage/app/content/content/blog 2>/dev/null

# Remove duplicate tree when files are confirmed under content/blog/
rm -rf backend/storage/app/content/content
```

Never delete `backend/storage/app/content/` itself.

---

## Verification checklist

On an isolated dev/staging instance:

- [ ] Create backup with default includes.
- [ ] Inspect ZIP: `unzip -l backup.zip | egrep 'content/(blog|pages)/'` shows `.md` files.
- [ ] Soft-delete one article (moves to trash).
- [ ] Restore the backup.
- [ ] Article visible in admin list and public URL.
- [ ] No new files under `storage/app/content/content/`.
- [ ] `./scripts/iteration-gate.sh` green after code changes.

PHPUnit regression: `BackupManagerTest::testCreateAndRestoreRoundTripIncludesPages`, `testCreateAndRestoreAfterSoftDeleteToTrash`.

---

## Related documents

- [STORAGE.md](../architecture/STORAGE.md) §11 — trash, backup policy
- [ISS-163](../ISSUES.md#iss-163) — incident register
- [FIRST_STEPS.md](../user/FIRST_STEPS.md) §11 — first backup drill
- [DEPLOY.md](../deploy/DEPLOY.md) — backup location outside web root
