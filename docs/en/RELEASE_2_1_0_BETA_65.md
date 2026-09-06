# Release `v2.1.0-beta.65` — Setup wizard server preflight (It.25 M1+)

> **Date:** 2026-09-05  
> **Tag:** `v2.1.0-beta.65`  
> **Type:** Onboarding / setup UX (It.25 stretch)

---

## One-line summary

The **`/setup` wizard** now verifies **server prerequisites** (PHP, extensions, storage, CLI tools) with **copy-paste install hints** — **no auto-install from the web** — and collects **infra defaults** (`backendPort`, `storageDriver`) before first login (ISS-162).

---

## What shipped

| Area | Change |
|------|--------|
| API | `GET /api/setup/preflight` — read-only checks + Ubuntu/Debian install steps |
| Wizard | Steps: **Server → Admin → Site → Infra → Finish** |
| Hard blockers | PHP ≥8.5, required extensions, writable `storage/` — continue disabled until green |
| Soft warnings | `gd`, `vendor/`, git/composer CLI — setup can proceed with hints |
| Complete payload | Optional `backendPort` → `systemUpdate`, `storageDriver` → `media` (`local` only) |
| Health | `SystemChecker` PHP minimum aligned to **8.5.0** |
| Security | No shell execution from setup; install commands are hardcoded whitelist only |
| Smoke | `scripts/smoke-it25.sh` calls preflight endpoint |
| **Backups (ISS-163)** | Full `content/` tree in ZIP; restore path fix; index rebuild + cache purge after restore; import CSRF + download UX |

---

## Wizard flow (fresh instance)

1. Open `/` → redirect to `/setup` when no users exist.
2. **Server** — preflight runs automatically; fix hard failures using displayed commands; **Refresh check**.
3. **Administrator** — first SUPER_ADMIN account.
4. **Site** — site name + admin locale.
5. **Infrastructure** — backend health port (default `8089`), media storage driver (`local`).
6. **Finish** — `general.installed = true`, redirect to **`/login`** (no auto-login; administrator must sign in explicitly).

---

## Preflight checks

| Check | Severity | Blocks setup |
|-------|----------|--------------|
| PHP ≥ 8.5 | hard | yes |
| Extensions: `json`, `mbstring`, `zip`, `curl`, `fileinfo` | hard | yes |
| Writable `storage/` tree | hard | yes |
| Extension `gd` | soft | no (thumbnails) |
| `vendor/autoload.php` | soft | no |
| `git` / `composer` CLI | soft | no (deploy/deps) |
| Docker runtime | info | no |

---

## API contract

### `GET /api/setup/preflight`

Pre-auth, CSRF-exempt (prefix `/api/setup/`).

```json
{
  "success": true,
  "data": {
    "ready": true,
    "hardBlockers": 0,
    "softWarnings": 1,
    "checks": [
      {
        "id": "php_version",
        "status": "pass",
        "severity": "hard",
        "current": "8.5.2",
        "required": ">= 8.5.0",
        "installSteps": []
      }
    ]
  }
}
```

### `POST /api/setup/complete` (extended)

Existing fields unchanged. Optional:

- `backendPort` — string, max 8 chars → `systemUpdate.backendPort`
- `storageDriver` — `local` only → `media.storageDriver`

Success response (no session created):

```json
{
  "success": true,
  "installed": true,
  "loginRequired": true,
  "redirectTo": "/login"
}
```

The frontend performs a full navigation to `/login?setup=complete&email=…` so the user signs in with the new credentials.

---

## Backup and restore (ISS-163)

| Fix | Detail |
|-----|--------|
| ZIP contents | Default backup includes `content/pages`, `content/blog`, `content/media`, `content/data`, … |
| Legacy import | Root `data/` in old ZIPs still merges into `content/data/` |
| Restore paths | Files land in `storage/app/content/blog/` (not `content/content/blog/`) |
| Post-restore | Content index rebuilt; list/payload caches purged |
| Admin UI | Import uses CSRF; download uses direct link; clearer restore toasts |

**Operator note:** backups created **before** this fix may not contain articles/pages. After upgrade, create a **new** production backup before relying on restore for content DR.

Full runbook: [BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md).

## Verification

- [ ] Clean dev instance → `/setup` → Server step shows checks
- [ ] Hard failure (e.g. unwritable storage) disables **Continue**
- [ ] Complete wizard → **login page** (not dashboard); sign in with new admin account
- [ ] `./scripts/smoke-it25.sh` — preflight HTTP 200
- [ ] `./scripts/iteration-gate.sh` green
- [ ] Backup drill: create → soft-delete article → restore → article visible ([BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md))

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-65)
- [ISS-162](ISSUES.md#iss-162)
- [ISS-163](ISSUES.md#iss-163)
- [BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md)
- [ITERATION_25.md](ITERATION_25.md)
- [INSTALLATION.md](user/INSTALLATION.md) §7
