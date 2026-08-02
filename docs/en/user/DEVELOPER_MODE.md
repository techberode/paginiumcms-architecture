---
title: Developer Mode
description: Unlock, session gate, dev tokens, and safe production configuration
icon: material/shield-key-outline
---

# Developer Mode — Security Gate

> Developer Mode unlocks dangerous administrative operations such as Code Editor and developer logs.  
> It is not a separate role, an RBAC bypass, or permission to edit Core.

---

## 1. Three access conditions

```text
feature availability
  + authenticated privileged session
  + valid Developer Mode unlock
  = temporary access to a gated operation
```

All conditions are enforced server-side. A hidden menu item or React route guard is not a security control.

---

## 2. Gate status

```http
GET /api/admin/developer/status
```

Typical fields:

| Field | Meaning |
|-------|---------|
| `feature_available` | mode is allowed by server configuration |
| `unlocked` | current session is unlocked |
| `unlocked_until` | unlock expiration time |
| `method` | for example `totp` or `token:<label>` |

Status must not expose a token hash, `DEV_UNLOCK_SECRET`, TOTP secret, or a list of sensitive paths.

---

## 3. Server availability

Example development profile:

```env
APP_ENV=development
APP_DEBUG=true
DEVELOPER_MODE=true
DEV_UNLOCK_SECRET=replace-with-long-random-secret
```

Safe production profile:

```env
APP_ENV=production
APP_DEBUG=false
DEVELOPER_MODE=false
```

The exact precedence of `DEVELOPER_MODE`, `APP_DEBUG`, and `APP_ENV` must match backend implementation. Documentation does not recommend enabling `APP_DEBUG` in production merely to access Code Editor.

After changing `.env`, restart the relevant PHP worker/container and verify the status endpoint through the same reverse-proxy path used by the admin SPA.

---

## 4. Unlock with TOTP

```http
POST /api/admin/developer/unlock
Content-Type: application/json

{ "totp_code": "123456" }
```

Requirements:

- authenticated privileged session,
- active user 2FA,
- valid short-lived TOTP code,
- rate/attempt limiting,
- CSRF protection for cookie sessions,
- audit of both success and repeated failures.

A TOTP unlock does not use the sample code shown in documentation. Server time must be synchronized.

---

## 5. Unlock with a dev token

```http
POST /api/admin/developer/unlock
Content-Type: application/json

{ "token": "pagdev_…" }
```

Generation/registration using available CLI tools:

```bash
php backend/bin/dev-token.php --label=workstation
php backend/bin/dev-token-register.php
```

Dev-token rules:

- secret value is displayed only at creation,
- registry stores a hash rather than plaintext,
- label identifies device/purpose,
- one token is not shared across people or machines,
- rotate after suspicion or loss,
- production should keep Developer Mode disabled,
- token never belongs in shell history, issues, CI logs, or Git.

When CLI prints a token, use a secure terminal session and clear it from clipboard history after registration.

---

## 6. TTL and session scope

Unlock is bound to a specific authenticated session and has a limited lifetime; the historical default is around eight hours. Exact TTL is runtime policy, not a security promise in documentation.

Unlock must not transfer:

- to another browser profile,
- to another device,
- to an API key/JWT identity,
- after logout/password reset/session invalidation,
- to another user in a recycled session.

A sensitive operation may require a fresh unlock before general TTL expires, for example after a security-context change.

---

## 7. Manual lock

```http
POST /api/admin/developer/lock
```

After work, click **Lock editor**. Lock:

- clears Developer Mode state for the session,
- does not log out of the CMS,
- prevents further Code Editor list/read/write access,
- should account for unsaved frontend changes,
- creates an audit record.

Closing the tab is not a reliable lock.

---

## 8. Protected capabilities

Implemented or documented gated capabilities include:

- Code Editor list/read/save/create/delete/restore,
- developer logs,
- potential future extension/theme scaffold and advanced layout authoring.

Developer Mode must not automatically expose:

- Core, bootstrap, or vendor writes,
- shell/terminal,
- arbitrary SQL or database administration,
- secret dumps,
- WAF/CSRF/RBAC bypass,
- autonomous publish,
- unrestricted filesystem.

Each capability still has its own permission, input validation, and policy.

---

## 9. Reverse proxy and session problems

When unlock works directly against backend but not through the public admin URL, check:

- cookie domain/path/Secure/SameSite,
- `APP_URL` and trusted-proxy configuration,
- HTTPS termination,
- forwarded `Host`/`X-Forwarded-*`,
- CSRF origin checks,
- sticky sessions or shared session storage across workers,
- server clock for TOTP.

Do not add the entire internet to `TRUSTED_PROXIES`. Use the specific reverse proxy or a trusted deployment subnet.

---

## 10. Logging and incident response

Audit at least:

- actor/user ID,
- unlock method without secret value,
- success/failure and reason code,
- session/request ID,
- timestamp and source IP according to privacy policy,
- lock and expiry,
- subsequent gated write operations.

On suspicion:

1. lock session and sign out the user,
2. disable Developer Mode,
3. rotate `DEV_UNLOCK_SECRET` and dev tokens,
4. inspect audit, Code Editor backups, and Git diff,
5. restore from a verified backup if code changed,
6. run the security/test gate.

---

## Related documents

- [Code Editor](CODE_EDITOR.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Security architecture](../architecture/CORE_HARDENING.md)
- [Deployment](../deploy/NGINX_API.md)
