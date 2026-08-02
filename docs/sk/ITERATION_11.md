---
title: Iterácia 11 – SSO, jemné ACL a bezpečnostný audit log
description: Historický záznam OAuth2 SSO, path ACL, nastaviteľného RBAC a security auditu
icon: material/history
---

# Iterácia 11 – SSO, jemné ACL a bezpečnostný audit log

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; neskôr hardenované |
| Release / obdobie | 2.0.27 + neskoršie ACL/SSRF opravy |
| Typ záznamu | historická bezpečnostná iterácia |

## Cieľ

Dodať OAuth2 SSO pre GitHub a generic provider, path-level ACL nad RBAC a samostatný security audit store s admin UI a CSV exportom. SAML nepatril do v1 rozsahu.

## Backend a routy

| Oblasť | Implementácia |
|---|---|
| SSO | `OAuthSsoService`, public provider/start/callback routy |
| ACL | `AclRepository`, `PathAclService`, `data/security/acl.json` |
| Audit | `SecurityAuditStore`, `SecurityLogger`, list/export API |
| Nastavenia | `sso` a neskôr preferované `accessControl` |

Audit vyžaduje ADMIN + 2FA; legacy ACL endpointy SUPER_ADMIN + 2FA. Preferovaný model presunul RBAC a Path ACL do Settings → Oprávnenia rolí.

## ACL semantika

ACL je opt-in, obmedzuje iba matching cesty a `SUPER_ADMIN` má bypass. Neskorší [ISS-055](ISSUES.md#iss-055) doplnil skutočné zapojenie do content/media operácií cez `ContentPathAclGuard`.

## Bezpečnostné doplnenia

OAuth redirect/state, default rola, secret encryption, CSV injection a outbound SSRF ochrana boli neskôr sprísnené. Aktuálny kontrakt je v [developer/SECURITY.md](developer/SECURITY.md) a [ACCESS_CONTROL.md](user/ACCESS_CONTROL.md).

