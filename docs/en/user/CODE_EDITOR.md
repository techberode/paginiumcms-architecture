---
title: Code Editor
description: Safe work with allow-listed source files through Monaco
icon: material/code-braces
---

# Code Editor — User Guide

> **Route:** `/code-editor`  
> **Risk:** high—this edits source or configuration files, not normal content.

Code Editor is a controlled administration tool for an experienced operator/developer. An invalid PHP or config file can cause HTTP 500 errors, an unavailable admin, a security issue, or failed application boot.

---

## 1. When not to use it

| You need to change | Use |
|--------------------|-----|
| page text | Pages / Content Editor |
| article | Articles |
| menu | Navigation |
| logo/colors | Settings → Branding/Appearance |
| images | Media |
| plugin enable/disable/import | Extensions |
| production `.env` | secure server deployment workflow, not a browser editor |

Code Editor is not a replacement for Git, an IDE, code review, or CI.

---

## 2. Protection layers

```text
privileged authenticated session
  → 2FA/security policy
  → Developer Mode unlock
  → allow-list path resolver
  → syntax + Code Policy
  → backup
  → confirmed write
  → audit/test/redeploy
```

A frontend button is not authority. Backend repeats gate, permission, canonical path, and policy checks for every operation.

---

## 3. Prerequisites

- user has the required admin permission,
- 2FA is active when unlock uses TOTP,
- Developer Mode is allowed by the server,
- session is unlocked,
- backup storage is writable,
- a current verified backup or Git commit exists.

Production should default to:

```env
APP_ENV=production
APP_DEBUG=false
DEVELOPER_MODE=false
```

---

## 4. Allowed roots

Currently documented allow-list:

| Root | Purpose |
|------|---------|
| `backend/app/Modules` | trusted internal modules |
| `backend/app/Http/Extensions` | external extension sources |
| `backend/resources/views/themes` | theme/template sources |
| `backend/config` | application configuration |

At minimum, the following are forbidden:

```text
backend/app/Core
backend/bootstrap
backend/vendor
storage secrets/log exports outside a supported API
.env
paths outside project root
```

The allow-list does not mean every file under an allowed root is safe to change. `backend/config` can break boot as seriously as Core.

Canonical-path checks must block `../`, double encoding, symlink escapes, NUL bytes, and case tricks on case-insensitive filesystems.

---

## 5. Unlock

1. Open Code Editor.
2. Enter a current TOTP code or registered dev token.
3. Confirm **Unlock Developer Mode**.
4. Verify UI shows expiration and method without displaying the secret.

See [DEVELOPER_MODE.md](DEVELOPER_MODE.md) for gate and token details.

---

## 6. Tree and file open

The editor loads only existing files under allowed roots. On open:

- backend revalidates the path,
- content is loaded as text under a size limit,
- binary and unsupported types are rejected,
- UI tracks dirty state,
- an out-of-band disk change may create a conflict.

The target contract should use a revision/fingerprint and reject overwriting a newer external version. If the current endpoint does not yet guarantee OCC, reload the file or inspect Git diff before save.

---

## 7. Save

Before writing:

1. inspect the diff,
2. run formatting/lint when available,
3. click **Save**,
4. read the confirmation,
5. backend creates a pre-save backup,
6. syntax and policy must pass,
7. file is written through the supported safe process,
8. result is audited.

A policy failure typically returns `422` with grouped errors. Do not remove security controls merely to make save “pass.”

### Untrusted extension/theme code

Untrusted paths must use enforced `validateUntrusted` policy even when the general Code Editor policy switch is relaxed for internal development. Import, Monaco, and future scaffold must not provide parallel weaker write paths.

---

## 8. Create a file

In **New file**:

1. choose an allowed root,
2. enter a relative path,
3. use a safe template,
4. verify namespace and `strict_types`,
5. save through the same policy pipeline.

Do not create a plugin as a lone `Hooks.php` without a manifest, tests, and lifecycle. The Code Editor wizard/scaffold is not documented as a complete universal authoring flow.

---

## 9. Delete and restore

### Delete

- requires explicit confirmation,
- creates a backup before deletion,
- active plugin/theme/config should first be disabled or switched,
- deleting a file may not update plugin registry, routes, or frontend build.

### Restore

```http
POST /api/admin/code-editor/restore
```

Select a backup for the exact file. Current state should be backed up before restore. After restoration, run syntax/test/health checks; an older file may not be compatible with a newer manifest or config schema.

---

## 10. API family

| Method | Route | Purpose |
|--------|-------|---------|
| `GET` | `/api/admin/developer/status` | gate status |
| `POST` | `/api/admin/developer/unlock` | TOTP or dev-token unlock |
| `POST` | `/api/admin/developer/lock` | lock session |
| `GET` | `/api/admin/code-editor/directories` | allowed roots |
| `GET` | `/api/admin/code-editor/files?directory=all` | file list/tree |
| `GET` | `/api/admin/code-editor/file?path=…` | load content |
| `POST` | `/api/admin/code-editor/save` | save |
| `POST` | `/api/admin/code-editor/file` | create |
| `DELETE` | `/api/admin/code-editor/file?path=…` | delete |
| `POST` | `/api/admin/code-editor/restore` | restore backup |

Exact response envelopes follow [API_CONTRACT.md](../architecture/API_CONTRACT.md). Every route repeats auth, CSRF for cookie mutations, permission, Developer Mode, and path policy.

---

## 11. What save does not do automatically

Saving a source file does not automatically guarantee:

- PHP worker/opcache reload,
- Vite frontend bundle rebuild,
- plugin manifest registration,
- plugin enable,
- route cache refresh,
- Git commit/push,
- successful test or deployment.

Follow the deployment profile after a change. Frontend extension source added to `frontend/src/extensions` requires build/redeploy; it does not magically enter the existing bundle.

---

## 12. Recommended workflow

```text
backup/Git clean
→ unlock
→ open and verify revision
→ small change
→ save + policy
→ syntax/unit test
→ health/smoke test
→ build/reload as needed
→ Git diff/commit
→ lock Developer Mode
```

Make one small change at a time. A browser editor is not the place for a 40-file refactor—that belongs in a local IDE, branch, tests, and pull request. The old craft still applies: backup first, courage second. 🙂

---

## 13. Recovery when CMS is unavailable

When admin or API fails after save:

1. stop refreshing and making more edits,
2. access the server through a secure administration channel,
3. inspect PHP syntax and log,
4. restore the last Code Editor backup or Git revert,
5. restart worker/opcache as required,
6. run health endpoint and test gate,
7. keep Developer Mode disabled until root cause is closed.

Backups are typically stored under `storage/backups/code/`; verify exact runtime path and retention in implementation/deployment documentation.

---

## 14. Lock after work

Click **Lock editor**. Before locking, UI should warn about unsaved changes. Lock is not logout, but it clears gated-operation access for the session.

In production, restore the safe environment profile and restart backend after finishing.

---

## Related documents

- [Developer Mode](DEVELOPER_MODE.md)
- [Plugins](PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Plugin architecture](../architecture/PLUGINS.md)
- [Deployment and reverse proxy](../deploy/NGINX_API.md)
