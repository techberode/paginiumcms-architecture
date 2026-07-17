# Iteration 11 – SSO, Fine-Grained ACL & Security Audit Log

**Status:** Planned  
**Version:** — (enterprise phase)

## Summary

Single sign-on (SAML/OAuth), file-level ACL beyond role-based permissions, and a complete security audit trail with admin UI and CSV export.

## Goals

| Area | Scope |
|------|-------|
| SSO | SAML/OAuth providers, flat-file config, role mapping |
| ACL | Per-path permissions in JSON; extend `AuthorizationManager` |
| Audit | Full `Modules/Audit` coverage: actions, CSV export, FE overview |
| Security log | Failed logins, permission denials, settings changes |

## Proposed backend

```
Modules/Security/Services/SsoProvider.php
Modules/Security/Services/AclRepository.php
Modules/Audit/ – extend existing engine + export
Http/Routes/sso.php
```

## Proposed frontend

- SSO login buttons on `/login`
- ACL editor in admin (path → role/permission matrix)
- Audit log viewer with filters + CSV download

## Dependencies

- ✅ Iteration 5 – auth foundation
- ✅ Iteration 20 – RBAC on mutations
- ⏳ Iteration 21 – stable API contract for audit endpoints

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 11
- [CORE_HARDENING.md](architecture/CORE_HARDENING.md) – current RBAC model

## Next

→ [Iteration 12](ITERATION_12.md) – Blueprint / Schema Engine
