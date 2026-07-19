# Iteration 11 – SSO, Fine-Grained ACL & Security Audit Log

**Status:** ✅ Complete  
**Release:** 2.0.27

## Summary

OAuth2 single sign-on (GitHub + generic provider), path-level ACL layered on RBAC, and a dedicated security audit log with admin UI and CSV export. SAML is **not** in v1 scope.

## Delivered

| Deliverable | Status |
|-------------|--------|
| OAuth2 SSO — GitHub + generic provider | ✅ |
| Settings group `sso` (flat-file via `SettingsRepository`) | ✅ |
| `GET /api/auth/sso/providers`, `/start`, `/callback` | ✅ |
| SSO buttons on public login modal | ✅ |
| Path ACL — `data/security/acl.json` + glob rules | ✅ |
| `GET/PUT /api/admin/security/acl` | ✅ |
| Admin ACL editor (`/security/acl`) | ✅ |
| Security audit store — `data/security/audit_events.json` | ✅ |
| `GET /api/admin/security/audit` + CSV export | ✅ |
| Admin audit viewer (`/security/audit`) | ✅ |
| `SecurityLogger` → audit events (login, permission, settings, SSO) | ✅ |
| PHPUnit smoke for ACL, audit, SSO providers | ✅ |

## Backend

```
Modules/Security/Services/OAuthSsoService.php      # GitHub + generic OAuth2 (curl)
Modules/Security/Services/AclRepository.php        # data/security/acl.json
Modules/Security/Services/PathAclService.php       # glob match on content paths
Modules/Security/Services/SecurityAuditStore.php   # data/security/audit_events.json
Core/Security/SecurityLogger.php                   # append to SecurityAuditStore
Http/Controllers/Auth/SsoController.php
Http/Controllers/Admin/AclController.php
Http/Controllers/Admin/SecurityAuditController.php
Http/Routes/sso.php
Http/Routes/security.php
```

### API routes

| Method | Route | Auth | FE client |
|--------|-------|------|-----------|
| `GET` | `/api/auth/sso/providers` | public | `securityApi.listSsoProviders()` |
| `GET` | `/api/auth/sso/{provider}/start` | public | `securityApi.startSso()` |
| `GET` | `/api/auth/sso/{provider}/callback` | public | redirect handler |
| `GET` | `/api/admin/security/audit` | ADMIN+2FA | `securityApi.listAudit()` |
| `GET` | `/api/admin/security/audit/export` | ADMIN+2FA | `securityApi.exportAuditCsv()` |
| `GET` | `/api/admin/security/acl` | ADMIN+2FA | `securityApi.getAcl()` |
| `PUT` | `/api/admin/security/acl` | ADMIN+2FA | `securityApi.saveAcl()` |

### ACL semantics

- ACL is **opt-in**: `enabled: false` → all paths allowed (RBAC only).
- When enabled, only paths matching a rule are restricted; unmatched paths stay open.
- Rules use glob prefix (`content/pages/finance/*`); roles or permissions on the rule must match.
- `SUPER_ADMIN` bypasses path ACL.

### SSO settings (`sso` group)

- `enabled`, `defaultRole` (new auto-provisioned users)
- GitHub: `githubEnabled`, `githubClientId`, `githubClientSecret`
- Generic OAuth2: `genericEnabled`, URLs, scope, client credentials

Public settings expose `sso.enabled` only (no secrets).

## Frontend

- `frontend/src/api/security.ts` — typed audit/ACL/SSO client
- `SecurityAuditManager.tsx` — `/security/audit` (filters, CSV download)
- `AclManager.tsx` — `/security/acl` (enable toggle, rule matrix)
- `LoginModal.tsx` — SSO provider buttons when enabled
- `AdminSidebar.tsx` — Bezpeč. audit + ACL pravidlá links

## Tests

| Suite | File |
|-------|------|
| PHPUnit | `PathAclServiceTest` — role match / deny on restricted path |
| PHPUnit | `SecurityAuditControllerTest` — auth + admin list |
| PHPUnit | `SsoControllerTest` — providers envelope |

## Out of scope (v1)

- SAML provider
- Wiring `PathAclService` into every content mutation (service ready; hook in next pass if needed)

## Dependencies (met)

- ✅ Iteration 5 – auth foundation
- ✅ Iteration 20 – RBAC on mutations
- ✅ Iteration 21 – JsonResponder API contract

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 11
- [architecture/API.md](architecture/API.md) – security endpoints
- [CHANGELOG.md](../CHANGELOG.md) – `[2.0.27]`

## Next

→ [Iteration 12](ITERATION_12.md) – Blueprint / Schema Engine
