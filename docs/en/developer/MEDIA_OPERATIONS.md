# Media library — operations and content integrity

> **Audience:** operators and developers  
> **UI:** Admin → Media  
> **API:** `/api/media/*` (auth + `media:upload` / `media:delete`)

---

## Upload (single and multi-file)

- Use **Upload files** or drag-and-drop onto the drop zone. Multiple files are sent **once each** (deduplicated by name/size/timestamp; concurrent upload batches are ignored).
- After a drag-and-drop, the drop zone no longer opens the file picker from the synthetic click that followed the drop (fixes duplicate uploads reported in [ISS-200](../ISSUES.md#iss-200)).

Uploaded binaries use secure naming when enabled (`image_YYYYMMDD_*.jpg` under `media/`). Registry: `storage/app/content/media/registry.json`.

---

## Rename / move file (integrated)

**API:** `POST /api/media/move`

```json
{ "from": "media/campaign/hero.jpg", "to": "media/campaign/hero-v2.jpg" }
```

- Updates disk + `registry.json` (same as internal folder moves).
- **Rewrites references** in all pages and blog articles (`/storage/app/content/…`, `/api/media/file/…`, and raw `media/…` paths) via `ContentMediaReferenceRewriter`.
- Purges content caches after rewrite.

**Admin UI:** Media grid/table → **Rename** on a file row.

Folder rename/move/copy/delete behaves as before (`POST /api/media/folders/*`).

---

## Hero / OG images in articles

Article heroes use **SEO → OG / preview image** in the editor (stored as `seoImage` + `featuredImage` in front matter).

- Editor loads OG from locale slice **or** flat `ogImage` when the slice was empty ([ISS-200](../ISSUES.md#iss-200)).
- Public render prefers **`seoImage` over stale `featuredImage`** when both are set.
- Broken preview URLs show an inline warning in the SEO panel.

---

## Blank thumbnails in admin (registry vs disk)

The media list is driven by **`media/registry.json`**. Thumbnails load from `/api/media/file/…`, which returns **404** when the binary path in the registry does not exist on disk.

Typical after a **partial or mixed restore**: registry restored from backup A, files missing or from another snapshot.

**CLI:** `media:reconcile` (`MediaRegistryReconcileService`)

| Option | Effect |
|--------|--------|
| `--json` | Machine-readable report; exit code **1** when drift detected |
| `--prune-registry` | Drop registry rows whose file path does not exist on disk |

**Diagnose:**

```bash
php backend/bin/console media:reconcile --json
```

Report fields: `registryMissingFile` (ghost rows → blank admin thumbs), `diskWithoutRegistry` (orphan binaries not in registry).

**Cleanup ghost rows** (does not fix article URLs — run `content:verify-media` too):

```bash
php backend/bin/console media:reconcile --prune-registry
```

Then re-upload assets or repeat a **full** backup restore that includes `content/media/` from the same archive.

---

## Backup restore and broken media

See [BACKUP_RESTORE.md](BACKUP_RESTORE.md):

- Full restore prunes orphan files under `blog/`, `media/`, etc. using the backup `manifest.json`.
- Restore writes media binaries with **`writeBinary()`** (not UTF-8 text write).
- After restore, admin backup API returns `mediaIntegrity`, `mediaRegistry`, and `archiveMedia` (see [BACKUP_RESTORE.md](BACKUP_RESTORE.md)).

| CLI | Purpose |
|-----|---------|
| `content:verify-media --json` | Broken `/storage/…` in pages/articles |
| `media:reconcile --json` | `registry.json` vs disk (blank admin thumbs) |
| `backup:inspect-media <id\|zip> --json` | Manifest media paths vs bytes inside a backup ZIP chain |

**Recovery (maintenance window):**

1. Full restore from one backup that contains **both** `content/blog/` and `content/media/` (default `includes: ["content","config"]`). Prefer admin Restore or `backup:restore-archive` — not manual unzip.
2. `media:reconcile --json` then `content:verify-media --json`; optional `backup:inspect-media` on the same ZIP before/after.
3. Re-upload missing assets only if verify-media still reports gaps (archive was already inconsistent).
4. Set OG image per article in SEO panel → **Save** (or rename/move files in Media if paths changed — references rewrite automatically).

Off-site copies (Nextcloud/WebDAV) are for DR only — restore always from local `storage/backups/` or a downloaded ZIP; see [ISS-201](../ISSUES.md#iss-201).

---

## Related

- [BACKUP_RESTORE.md](BACKUP_RESTORE.md)
- [ISS-163](../ISSUES.md#iss-163) — restore path contract
- [ISS-200](../ISSUES.md#iss-200) — Oct 2026 prod incident (tags, media, hero)
- [ISS-201](../ISSUES.md#iss-201) — daily backup + Nextcloud off-site
