# Backup and restore

> **Audience:** operators and developers  
> **Services:** `BackupManager`, `BackupOffsiteWebDavService`  
> **Admin UI:** Platform → Backups · Settings → System → **Backups (off-site)**  
> **API:** `/api/admin/backups/`* (auth + `backups:*` permissions)

**Quick links:** [Scheduled cron](#scheduled-backup-cron) · [Nextcloud WebDAV](#nextcloud-webdav-off-site-operator-guide) · [CLI](#cli-reference) · [Restore](#restore-backup) · [Security](#security-and-credentials)

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


| ZIP prefix            | Source on disk                    | Notes                                                        |
| --------------------- | --------------------------------- | ------------------------------------------------------------ |
| `content/pages/`      | `storage/app/content/pages/`      | Markdown/JSON pages                                          |
| `content/blog/`       | `storage/app/content/blog/`       | Articles                                                     |
| `content/media/`      | `storage/app/content/media/`      | Local media objects                                          |
| `content/data/`       | `storage/app/content/data/`       | Settings, users, indexes, jobs, …                            |
| `content/trash/`      | `storage/app/content/trash/`      | Soft-deleted files + sidecars                                |
| `content/navigation/` | `storage/app/content/navigation/` | When present                                                 |
| `config/`             | `storage/app/config/`             | Optional when `includes` contains `config`                   |
| `backup.json`         | —                                 | Metadata snapshot at create time                             |
| `manifest.json`       | —                                 | Per-file SHA-256 map of the selected scope (full tree state) |
| `deletes.json`        | —                                 | Incremental only: ZIP paths removed since the baseline       |


Allowed `includes` flags: `content` (entire `storage/app/content/` tree), `pages`, `blog`, `media`, `data`, `navigation`, `trash`, `config`. If `content` is set, granular content flags are ignored. Default: `["content", "config"]`.

### Incremental (rsync-style) backups

`POST /api/admin/backups` and `POST /api/admin/backups/schedule` accept `"mode": "full" | "incremental"`.

Incremental does **not** invoke the `rsync` binary. It compares SHA-256 hashes against the latest completed backup with the **same scope** and writes only changed/new files into the ZIP. Deleted files are listed in `deletes.json`. The first incremental with no matching baseline is stored as a **full** snapshot.

Restore of `mode=incremental` walks `baseBackupId` to the full baseline, restores that ZIP, then each delta (merge + deletes). Retention (`keep`) never drops a baseline still required by a kept incremental.

Scheduled backups persist `includes` and `mode` in `storage/backups/schedule.json`.

### Scheduled backup (cron)

**Admin:** Backups → enable schedule → **Daily**, **Full**, scope **Content (full tree)** + **Config**, set **Keep** (e.g. 7–14).

**Off-site (Nextcloud WebDAV):** Settings → System → **Backups (off-site)** — after each successful local backup the ZIP is uploaded via WebDAV PUT (scheduled + manual). Google Drive is not built-in; use rclone below if needed.

**Cron (Docker example, 02:00):**

```cron
0 2 * * * flock -n /run/lock/paginium-backup.lock /path/to/stack.sh exec -T -u www-data php php backend/bin/console backup:run-schedule >> /var/log/paginiumcms/backup.log 2>&1
```

Alternative: enable scheduler job `backup-scheduled` (`backup.scheduled`) with expression `0 2 * * *` and run `scheduler:run` every minute ([CRON.md](../../deploy/CRON.md)). Both paths call the same `runScheduledBackupIfDue()` gate (`schedule.json` `next_run`).

**Tips:**

- Default includes `["content","config"]` = pages, blog, **media**, data, navigation, trash + app config — suitable for daily disaster recovery.
- Prefer `mode: full` for nightly jobs unless disk is tight; incremental needs an intact baseline chain.
- After enabling the schedule, run once manually: `php backend/bin/console backup:run-schedule` (confirms ZIP + `backup:inspect-media` healthy).
- Cron at 02:00 only runs when `next_run` in `schedule.json` is due; if the first run was scheduled for later the same day, either wait or trigger manually once.
- **Scheduled off-site:** uses the same upload pipeline as manual backups. Metadata JSON lists `offsiteUploaded` / `offsiteError`. CLI prints WebDAV status after `backup:run-schedule`. Cron/scheduler must load the same `.env` as PHP-FPM (`APP_KEY` decrypts the WebDAV app token) — see [Security and credentials](#security-and-credentials).
- **ZIP size on disk:** Settings → Backups off-site → **ZIP compression (0–9)** (`backupZipDeflateLevel`, default 6). Lower hosting use without changing backup scope.

### Nextcloud WebDAV off-site (operator guide)

PaginiumCMS uploads each completed backup ZIP to your Nextcloud (or any WebDAV server) **after** the local archive is written. Local copies in `storage/backups/` are always kept for fast restore; off-site is a **second copy**.

#### What URL to use (common mistake)


| Wrong (browser / Files app)                                  | Correct (WebDAV base URL in CMS)                           |
| ------------------------------------------------------------ | ---------------------------------------------------------- |
| `https://cloud.example.com`                                  | `https://cloud.example.com/remote.php/dav/files/LOGIN`     |
| `https://cloud.example.com/apps/files/files/123?dir=/Folder` | Same WebDAV base; set **folder** in the separate CMS field |


`LOGIN` = the username you use to sign in to Nextcloud (not necessarily your email).

**Example shape** (replace host, user, and folder with yours):


| Setting (Settings → System → Backups off-site) | Example value                                              |
| ---------------------------------------------- | ---------------------------------------------------------- |
| Enable upload                                  | On                                                         |
| WebDAV base URL                                | `https://<your-cloud-host>/remote.php/dav/files/<NC_USER>` |
| WebDAV username                                | `<NC_USER>` (same as in the URL path)                      |
| WebDAV password                                | Nextcloud **app password / auth token** (see below)        |
| Remote folder                                  | `<REMOTE_FOLDER>` (e.g. `MyCMS_backups`)                   |




#### App password / auth token (not your Nextcloud login password)

PaginiumCMS WebDAV upload uses **HTTP Basic auth** with the Nextcloud account name plus a **dedicated token**. Never put your normal Nextcloud login password in CMS settings. Tokens work with **2FA enabled** on the account.

**Option A — Nextcloud web UI**

1. Sign in as `<NC_USER>` → **Settings → Security → Devices & sessions**.
2. App name e.g. `PaginiumCMS_Zalohy` → **Create new app password**.
3. Copy the token **once** into CMS → **Save**. If lost, revoke the device and create a new token.

**Option B —** `occ` **on the Nextcloud server (Docker / AIO)**

Run on the **Nextcloud host** (not inside the PaginiumCMS container). Container name and Unix user depend on your install.

```bash
# Nextcloud All-in-One (example container name — adjust with: docker ps)
docker exec --user www-data -it <NEXTCLOUD_CONTAINER> \
  php occ user:auth-tokens:add <NC_USER> --name "PaginiumCMS_Zalohy"
```


| Placeholder             | Typical value             | How to find it                               |
| ----------------------- | ------------------------- | -------------------------------------------- |
| `<NEXTCLOUD_CONTAINER>` | `nextcloud-aio-nextcloud` | `docker ps` on the Nextcloud server          |
| `<NC_USER>`             | e.g. `admin`              | Nextcloud login / WebDAV path segment        |
| `--name`                | `PaginiumCMS_Zalohy`      | Label in Devices & sessions (revoke by name) |


The command prints the new token **once** on stdout. Paste it into CMS **WebDAV password**, then clear the shell scrollback or use a password manager — do not commit or paste tokens into tickets/chat.

Non-interactive (no TTY): drop `-it` or use `-T` only, e.g. `docker exec --user www-data -T <NEXTCLOUD_CONTAINER> php occ …`.

Revoke old tokens: Nextcloud UI → Devices & sessions, or `php occ user:auth-tokens:delete <NC_USER> <token-id>` (list IDs with `user:auth-tokens:list <NC_USER>`).

#### Verify upload

1. Admin → Backups → create a **full** backup (content + config).
2. Open the folder on Nextcloud — a `.zip` with the same basename as on the server should appear.
3. Optional CLI: `backup:inspect-media` on that ZIP (see [CLI](#cli-reference)).

Upload failures are written to the PHP error log (`backup offsite upload failed`); **local backup still succeeds**.

#### Production constraints

- `APP_ENV=production`: target URL must be **HTTPS** (`OutboundUrlGuard`).
- Credentials are stored encrypted in settings (`offsiteWebDavPassword`, type `password`).
- Backup ZIPs contain full site content and encrypted settings — treat the Nextcloud folder like a vault (access control, encryption at rest on the cloud side).

See [Security and credentials](#security-and-credentials) before testing with `curl`.

### Off-site copy (rclone — optional, e.g. Google Drive)

Alternative or second copy: **rclone** on the host after a successful local backup.

1. Install [rclone](https://rclone.org/) on the server (not inside a throwaway container unless configured there).
2. Configure a remote:
  - **Nextcloud:** `rclone config` → `webdav` → URL `https://your-cloud/remote.php/dav/files/USER/` + app password.
  - **Google Drive:** `rclone config` → `drive` → OAuth (headless `rclone authorize` on a workstation, paste token on server).
3. Sync only the backup directory (outside web root):

```bash
# Example: daily at 03:00, after CMS backup at 02:00
0 3 * * * rclone copy /var/www/paginiumcms.com/backend/storage/backups remote:PaginiumCMS/backups \
  --max-age 24h --transfers 2 --log-file /var/log/paginiumcms/rclone-backup.log --log-level INFO
```

Use `copy` or `sync` deliberately: `sync` deletes remote files not present locally (respects CMS retention after `keep` pruning). Encrypt the remote bucket or Nextcloud folder; backup ZIPs contain full site content and secrets in `data/`.

Optional: `rclone copy … && rclone delete … --min-age 30d` on the remote for long retention independent of CMS `keep`.

<a id="off-site-nextcloud-webdav"></a>

### Off-site retention (operator-managed)

PaginiumCMS uploads each successful local ZIP to WebDAV when enabled; it **does not** delete older files on Nextcloud. Plan manual cleanup (Files app, `occ`, or host `rclone delete --min-age …`) so remote storage does not grow without bound. Local ZIP count is still governed by Platform → Backups **keep**; trash and content-version retention are separate ([STORAGE.md](../architecture/STORAGE.md#14-storage-retention-trash-versions-drafts)).

**Optional encryption before upload:** Settings → **Backups (off-site)** → enable **Encrypt ZIP before off-site upload** and set **Off-site encryption key** (stored encrypted with `APP_KEY`, separate from WebDAV password). Remote file is `*.zip.enc` (`enc:bk:s1:` libsodium payload). Decrypt offline with the same passphrase before restore.

---



## Create backup

**API:** `POST /api/admin/backups`

```json
{
  "name": "nightly",
  "includes": ["content", "config"],
  "mode": "full"
}
```

Granular example: `{ "includes": ["pages", "blog", "config"], "mode": "incremental" }`.

**Integrity:** each backup stores `sha256` in metadata. Use **Verify** in admin or `GET /api/admin/backups/{id}/verify`.

### Legacy format (pre-fix backups)

Older archives may contain only `data/` at the **ZIP root** (no `content/` tree). Restore still accepts these: `data/` is merged into `storage/app/content/data/`.

**Important:** legacy backups created before the content-tree fix often **do not contain** `pages/`, `blog/`, or `media/`. Restoring them updates settings/indexes but **will not bring back articles or pages**. Create a **new backup** after upgrading to a fixed release before relying on restore for content disaster recovery.

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
7. **Prune scoped orphans** (when `manifest.json` is present): for each content subtree present in the manifest (`blog/`, `media/`, `pages/`, …), delete on-disk files that are **not** listed in the manifest. This makes a full restore a true point-in-time snapshot instead of a merge that leaves newer orphan media (which breaks hero/body image URLs in hundreds of articles).

After restore, the admin API returns:


| Field            | Meaning                                                                                |
| ---------------- | -------------------------------------------------------------------------------------- |
| `mediaIntegrity` | Broken `/storage/…` references in pages/articles (`content:verify-media`)              |
| `mediaRegistry`  | `registry.json` vs on-disk binaries (`media:reconcile`)                                |
| `archiveMedia`   | Whether the restored archive’s manifest listed media binaries present in the ZIP chain |


CLI equivalents: `content:verify-media --json`, `media:reconcile --json`, `backup:inspect-media <id\|zip> --json`.

Restore uses `writeBinary()` for content files (images are not passed through UTF-8 normalization). Re-run restore from the same full ZIP after upgrading if an earlier build left corrupt media.

### Restore semantics (merge vs “only missing files”)

Restore is **not** rsync-style “copy only if missing or hash differs”. It always:

1. **Overwrites** every file path present in the ZIP (merge into `storage/app/content/` and `config/`).
2. **Deletes** on-disk files under scoped subtrees (`blog/`, `media/`, `pages/`, …) that are **not** listed in the backup `manifest.json` (when the manifest exists).
3. Applies `deletes.json` paths for incremental archives.

It does **not** wipe the entire content tree before step 1. Subtrees **outside** the backup scope (e.g. restore with `includes: ["data"]` only) are untouched — that is a common cause of **registry vs missing binaries** in Media. See [MEDIA_OPERATIONS.md](MEDIA_OPERATIONS.md) (`media:reconcile`).


| Question                                           | Answer                                    |
| -------------------------------------------------- | ----------------------------------------- |
| Same path already on disk?                         | Overwritten with archive bytes            |
| File on disk but not in manifest (scoped subtree)? | Removed after restore (manifest prune)    |
| File missing on disk but in ZIP?                   | Written                                   |
| Compare SHA with live disk before write?           | No — every archived file is written       |
| Legacy ZIP without `manifest.json`?                | Merge overwrite only; **no** orphan prune |


**Incremental restore:** `resolveRestoreChain()` applies baseline full ZIP, then each delta in order (each pass: merge + deletes + manifest prune for that ZIP).

### Soft delete (trash) scenario

When an article is deleted through the CMS, the file moves to `content/trash/` and the index entry is removed. Restoring a backup taken **before** the delete:

- Writes the article back to `content/blog/{slug}.md`.
- Restores `data/index/content.json` from the archive **and** rebuilds the index after merge (authoritative = files on disk).
- Does **not** automatically remove trash sidecars created after the backup. If the same slug exists in both `blog/` and `trash/`, prefer the live `blog/` file; empty stale trash entries manually if needed.

Always verify restore with a **delete → restore** drill on a dev copy ([ISS-163](../ISSUES.md#iss-163)).

### Login / 2FA after restore

Restore brings back `data/users/*.json` **and** `data/settings.json` **from the backup timestamp**. It does **not** keep your current password or authenticator pairing from after that date.


| Symptom                       | Cause                                                                    | Recovery (CLI on server)                                                                                          |
| ----------------------------- | ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------- |
| **401** wrong password        | Backup has an **older password hash**                                    | `php backend/bin/console user:reset-password you@example.com 'NewSecurePass1!'`                                   |
| Password OK, **invalid TOTP** | Backup has an **old 2FA secret**; phone app still has the **new** secret | `php backend/bin/console user:disable-2fa you@example.com` then log in and set up 2FA again in Account → Security |
| **429** too many attempts     | Lockout from failed tries                                                | `php backend/bin/console security:clear-lockouts` (or wait)                                                       |
| User missing                  | Backup scope omitted `data` / users                                      | `php backend/bin/console user:list` — create with `user:create` if needed                                         |


`.env` `APP_KEY` is not restored from ZIP. If encrypted fields (`enc:`) in restored users cannot decrypt, reset password and disable 2FA via CLI, then re-enable 2FA.

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


| Symptom                                             | Likely cause                                                                                                                                                            | Action                                                                                                                                                                                                                                                |
| --------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Restore “succeeds” but articles/pages missing       | Legacy ZIP without `content/blog` or `content/pages`                                                                                                                    | Create a new backup on a fixed release; re-import prod content if needed                                                                                                                                                                              |
| File on disk under `content/content/`               | Restore path bug (fixed ISS-163)                                                                                                                                        | Upgrade, remove orphan `storage/app/content/content/`, run restore again                                                                                                                                                                              |
| Article file exists but admin list empty            | Stale content cache (fixed ISS-163)                                                                                                                                     | Upgrade; or purge cache via admin cache tools / restart PHP workers                                                                                                                                                                                   |
| Import fails with 403                               | Missing CSRF on upload (fixed ISS-163)                                                                                                                                  | Use current frontend; ensure `X-CSRF-TOKEN` on `POST /api/admin/backups/import`                                                                                                                                                                       |
| Download dialog stuck                               | Browser blob revoke race (fixed ISS-163)                                                                                                                                | Use current frontend (direct download link)                                                                                                                                                                                                           |
| Prod backup on dev shows no content                 | Expected if ZIP is legacy data-only                                                                                                                                     | Same as row 1                                                                                                                                                                                                                                         |
| Heroes show alt text; many broken `/storage/…` URLs | Blog restored without `media/` from the **same** backup, or pre-fix merge-only restore left mismatched files                                                            | Full restore with default `includes: ["content","config"]`; run `content:verify-media`; never mix blog from backup A with media from live disk                                                                                                        |
| Media grid shows metadata but **blank thumbnails**  | `media/registry.json` restored while binaries missing (partial scope, mixed snapshots, or legacy merge)                                                                 | `php backend/bin/console media:reconcile --json`; full restore with `content/media/` from one backup; optional `--prune-registry` then re-upload; [MEDIA_OPERATIONS.md](MEDIA_OPERATIONS.md)                                                          |
| Restore reports success but **files still missing** | ZIP never contained them (`includes` without `media`/`content`, legacy data-only backup, or incremental without baseline); backup taken when binaries were already gone | **Before restore:** `unzip -l storage/backups/<file>.zip | grep 'content/media/' | wc -l` — must be > 0 for images. Restore a `mode: full` backup with default scope, not import-only. After deploy with binary-safe restore fix, retry restore once. |




### Verify a backup actually contains media (before restore)

**CLI (≥ Unreleased):** accepts backup id, metadata `.json`, or direct `.zip` path:

```bash
php backend/bin/console backup:inspect-media backup_6acc8c69bdf462.96920436 --json
php backend/bin/console backup:inspect-media /path/to/downloaded-backup.zip --json
```

Restore a downloaded ZIP without library registration:

```bash
php backend/bin/console backup:restore-archive /path/to/downloaded-backup.zip
```

Manual unzip check on the server:

```bash
ZIP=$(php -r 'echo json_decode(file_get_contents("storage/backups/BACKUP_ID.json"), true)["filePath"] ?? "";')
unzip -l "$ZIP" | grep -E 'content/(media|blog)/' | head -20
unzip -l "$ZIP" | grep -c 'content/media/' || true
```

If the media count is **0**, restore cannot bring images back — pick another archive or restore files from off-site copy.

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



## CLI reference


| Command                                       | Purpose                                                                                                                               |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `backup:run-schedule`                         | Create backup when `storage/backups/schedule.json` is due (for crontab)                                                               |
| `backup:inspect-media <ref> [--json]`         | `<ref>` = backup id, path to `{id}.json` metadata, or path to `.zip` — compares manifest media paths vs binaries in the restore chain |
| `backup:restore-archive <path/to.zip>`        | Restore flat-file content from a downloaded ZIP (same pipeline as admin Restore)                                                      |
| `content:verify-media [--json]`               | Scan pages/articles for missing media paths                                                                                           |
| `media:reconcile [--json] [--prune-registry]` | Compare `media/registry.json` with disk                                                                                               |
| `user:reset-password <email> '<pass>'`        | After restore when password hash is from backup era                                                                                   |
| `user:disable-2fa <email>`                    | After restore when TOTP no longer matches phone app                                                                                   |
| `security:clear-lockouts`                     | Clear login rate-limit state                                                                                                          |


Scheduler integration: job `backup.scheduled` → `BackupScheduledHandler` → same `runScheduledBackupIfDue()` as `backup:run-schedule` ([CRON.md](../../deploy/CRON.md)).

---



## Security and credentials

- **Never** put Nextcloud tokens in shell history: avoid `curl -u 'user:password'`. Prefer CMS settings or `read -s` for one-off tests.
- `occ user:auth-tokens:add` prints the token to the terminal — treat that output like a password; revoke via Devices & sessions if it was logged or shared.
- If a token was exposed (terminal, chat, git), **revoke** it in Nextcloud (UI or `user:auth-tokens:delete`) → create a new token → update CMS settings.
- `curl -I` against Nextcloud may return **session cookies** in headers; that is not the app password, but do not paste those headers publicly.
- Off-site upload uses HTTP **PUT** with Basic auth (username + app password). URL is checked with `OutboundUrlGuard` before the request.

---



## Implementation notes (developers)


| Component                               | Role                                                                                                                                           |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `BackupManager::create()`               | Writes ZIP + metadata; calls `BackupOffsiteWebDavService` when enabled                                                                         |
| `BackupOffsiteWebDavService`            | WebDAV PUT to `{baseUrl}/{pathPrefix}/{zipFileName}`                                                                                           |
| `SettingsSchema` group `backups`        | `offsiteWebDavEnabled`, `offsiteWebDavUrl`, `offsiteWebDavUsername`, `offsiteWebDavPassword`, `offsiteWebDavPathPrefix` (super-admin settings) |
| `BackupManager::inspectMediaCoverage()` | Pre/post restore diagnostics                                                                                                                   |
| `BackupController::restoreBackup()`     | Returns `mediaIntegrity`, `mediaRegistry`, `archiveMedia`                                                                                      |


Google Drive is **not** implemented in-core; use [rclone](#off-site-copy-rclone--optional-eg-google-drive) or a second Nextcloud sync if needed.

---



## Verification checklist

On an isolated dev/staging instance:

- [ ] Create backup with default includes.
- [ ] Create a pages-only backup and confirm the ZIP has `content/pages/` but not `content/blog/`.
- [ ] Create a full backup, change one file, create incremental; ZIP should omit unchanged files.
- [ ] Restore the incremental; changed file and deletes match.
- [ ] Inspect ZIP: `unzip -l backup.zip | egrep 'content/(blog|pages)/'` shows `.md` files.
- [ ] Soft-delete one article (moves to trash).
- [ ] Restore the backup.
- [ ] Article visible in admin list and public URL.
- [ ] No new files under `storage/app/content/content/`.
- [ ] `./scripts/iteration-gate.sh` green after code changes.
- [ ] Settings → Backups off-site: manual backup → ZIP visible on Nextcloud (or WebDAV).
- [ ] `backup:inspect-media` reports `healthy: true` on a full backup after media upload drill.

PHPUnit regression: `BackupManagerTest::testCreateAndRestoreRoundTripIncludesPages`, `testCreateAndRestoreAfterSoftDeleteToTrash`, `testCreateRespectsGranularIncludes`, `testIncrementalBackupSkipsUnchangedFilesAndRestoresDelta`.

---



## Related documents

- [MEDIA_OPERATIONS.md](MEDIA_OPERATIONS.md) — upload, rename, hero/OG, recovery after restore
- [CRON.md](../../deploy/CRON.md) — `scheduler:run`, `backup-scheduled` job
- [STORAGE.md](../architecture/STORAGE.md) §11 — trash, backup policy
- [ISS-163](../ISSUES.md#iss-163) — restore path contract
- [ISS-200](../ISSUES.md#iss-200) — Oct 2026 prod media/restore incident
- [ISS-201](../ISSUES.md#iss-201) — scheduled backup + Nextcloud off-site
- [FIRST_STEPS.md](../user/FIRST_STEPS.md) §11 — first backup drill
- [DEPLOY.md](../deploy/DEPLOY.md) — backup location outside web root

