---
title: Iteration 11 – SSO, Fine-Grained ACL and Security Audit Log
description: Historical record of OAuth2 SSO, path ACL, configurable RBAC, and security auditing
icon: material/history
---

# Iteration 11 – SSO, Fine-Grained ACL and Security Audit Log

> **Historical delivery record.** This document describes the iteration as delivered and includes later fixes that were appended to the original record. For current architectural rules, the documents under `docs/architecture/`, the security policy, `ISSUES.md`, and the current release contract take precedence.

| Field | Value |
|---|---|
| Status | ✅ Complete; hardened later |
| Release / period | 2.0.27 + neskoršie ACL/SSRF opravy |
| Record type | historical security iteration |

## Goal

Deliver OAuth2 SSO for GitHub and a generic provider, path-level ACL layered on RBAC, and a dedicated security audit store with admin UI and CSV export. SAML was outside the v1 scope.

## Backend and routes

| Area | Implementation |
|---|---|
| SSO | `OAuthSsoService`, public provider/start/callback routes |
| ACL | `AclRepository`, `PathAclService`, `data/security/acl.json` |
| Audit | `SecurityAuditStore`, `SecurityLogger`, list/export API |
| Settings | `sso` and later the preferred `accessControl` group |

Audit requires ADMIN plus 2FA; legacy ACL endpoints require SUPER_ADMIN plus 2FA. The preferred model moved RBAC and Path ACL into Settings → Role permissions.

## ACL semantics

ACL is opt-in, restricts only matching paths, and `SUPER_ADMIN` bypasses it. Later [ISS-055](ISSUES.md#iss-055) wired it into real content/media operations through `ContentPathAclGuard`.

## Security additions

OAuth redirect/state, default roles, secret encryption, CSV injection, and outbound SSRF protection were hardened later. The current contract is in [developer/SECURITY.md](developer/SECURITY.md) and [ACCESS_CONTROL.md](user/ACCESS_CONTROL.md).

