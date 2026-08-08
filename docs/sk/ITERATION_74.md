# Iterácia 74 — API kľúče a krátko žijúce JWT

> **Stav:** ✅ dokončené v `[Unreleased]` (fázy 74a + 74b)  
> **Priorita:** 🟡  
> **Vlna:** [Hybrid Engine HE-5](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.68](ITERATION_68.md) · cache lookup z [It.69](ITERATION_69.md) je odporúčaný

## Potvrdené rozhodnutie

| Klient | Autentifikácia |
|--------|----------------|
| Admin SPA | existujúca PHP session + CSRF + RBAC + voliteľná 2FA zostáva bez zmeny |
| Serverová integrácia/CI | scope-limited API kľúč |
| Krátka delegovaná úloha | voliteľné krátko žijúce JWT vydané dôveryhodnému klientovi |
| Browser admin token v localStorage | zakázané |

It.74 **nenahrádza session autentifikáciu JWT**. Rozširuje headless kontrakt bez regresie ľudského admin flow.

---

## Hrozbový a dátový model API kľúčov

Kľúč má verejný identifikátor a tajnú časť zobrazenú iba raz, napríklad:

```text
pgk_<key-id>_<high-entropy-secret>
```

`data/api-keys.json` uchováva iba:

- `id`, label, owner/creator,
- hash/HMAC tajnej časti, nie plaintext,
- scopes a explicitný route policy profil,
- `createdAt`, `expiresAt`, `lastUsedAt`,
- revoke/rotation stav,
- voliteľný IP/egress hint iba ak je bezpečne implementovaný.

Odporúčaný verifier je HMAC-SHA-256 s oddeleným `API_KEY_PEPPER` alebo rovnocenný bezpečný hash pre náhodný high-entropy token. Porovnanie používa `hash_equals`. `APP_KEY` sa nemá bezdôvodne znovu používať pre všetky kryptografické účely.

---

## Fáza 74a — read-only API keys

MVP scopes:

- `content:read` — iba publikovaný/headless slice,
- `media:read` — podľa public/private policy,
- `settings:read` — iba explicitný public integration slice.

Každá route musí byť v allow-list mape. Samotný scope string nesprístupní automaticky všetky `/api/admin/*` endpointy.

Backend komponenty:

| Komponent | Zodpovednosť |
|-----------|--------------|
| `ApiKeyStore` | atomický flat-file SSOT, revoke/rotate, schema validation |
| `ApiKeyVerifier` | prefix parse, key lookup, constant-time verify |
| `BearerAuthMiddleware` | oddelený od session resolvera, stabilné 401/403 |
| `ApiScopePolicy` | route + method + required scopes |
| `ApiKeyRateLimitIdentity` | rate limit per key ID bez logovania secretu |
| Admin UI/API | create, copy once, list metadata, revoke, rotate |

---

## Fáza 74b — scoped write a JWT

Write scopes (`content:write`, `media:write`, neskôr `git:publish`) sú explicitný opt-in a vyžadujú rovnaké doménové validátory ako session flow.

JWT je určené iba na krátko žijúcu delegáciu:

- samostatný signing key `API_JWT_KEY` alebo asymetrický keypair podľa deployment policy,
- claims minimálne `iss`, `aud`, `sub`, `jti`, `iat`, `nbf`, `exp`, `scope`,
- krátke maximum TTL,
- žiadny dlhodobý refresh token v browser storage,
- audience a issuer sa povinne validujú,
- revoke kritických tokenov cez short TTL a voliteľný flat-file deny-list pre `jti`,
- algoritmus je server-side allow-listovaný; žiadne `alg=none` ani algoritmická zámennosť.

JWT issuing endpoint je dostupný iba autorizovanej session alebo API key klientovi so scope `token:issue` a explicitnou policy.

---

## CSRF a resolver pravidlá

- session mutation routes zostávajú CSRF-protected,
- Bearer routes nepoužívajú cookie session ako autoritu a sú CSRF-exempt,
- request s neplatným Bearer tokenom nesmie potichu fallbacknúť na menej privilegovaný alebo iný auth mechanizmus,
- admin SPA naďalej používa cookies/session; frontend refactor na JWT je non-goal,
- CORS policy sa nemení automaticky pridaním API keys.

---

## Bezpečnosť a prevádzka

- plaintext kľúč sa zobrazí iba pri vytvorení/rotácii,
- API response a audit nikdy nevracajú hash/HMAC ani secret,
- kľúče majú expiry, revoke a rotation workflow,
- `lastUsedAt` update nesmie pri každom requeste spôsobiť neúmerný lock/write; použije sa bounded/coalesced update alebo odvodená metrika,
- rate limit a audit používajú key ID,
- log sanitizer maskuje tokeny v headeroch a chybách,
- backup policy chráni key store; restore neobnoví už revokovaný kľúč bez jasného incident postupu,
- citlivé write scopes môžu vyžadovať nedávnu 2FA pri vytvorení kľúča.

---

## Frontend

Route `/platform/api-keys`:

- create wizard s label, scopes, expiry a vysvetlením,
- copy-once secret panel,
- list bez secretu: ID prefix, scopes, created/expiry, last used, status,
- revoke/rotate s potvrdením a auditom,
- príklady `curl` bez reálnych tokenov,
- varovanie, že kľúč patrí do secret managera, nie do Git repozitára alebo browser localStorage.

---

## Mimo rozsahu

- odstránenie PHP session,
- OAuth/OIDC authorization server,
- anonymné write endpointy,
- wildcard admin scope,
- dlhodobé browser refresh tokeny,
- SQL/Redis ako jediný key store,
- vydanie JWT heslom používateľa mimo existujúceho login/2FA flow.

---

## Testy

- valid read key → `200`, chýbajúci scope → `403`, revoked/expired → `401`,
- plaintext sa nedá získať druhýkrát,
- hash/HMAC constant-time verify a malformed prefix handling,
- route allow-list zabráni vstupu na neoznačené admin endpointy,
- write key prejde rovnakou schema/RBAC domain policy,
- session mutation stále vyžaduje CSRF,
- Bearer failure neprejde na session fallback,
- JWT validuje issuer, audience, expiry, nbf, jti a allowed algorithm,
- token/header redaction v logoch,
- concurrent key-store updates a rotation,
- Classic admin login je identický s `beta.23`.

---

## Definition of Done

- [ ] Externý klient prečíta publikovaný content cez scoped API key.
- [ ] Kľúč je uložený iba ako bezpečný verifier; secret je copy-once.
- [ ] Route/method allow-list a per-key rate limit sú aktívne.
- [ ] Revoke/rotate/expiry/audit workflow je end-to-end.
- [ ] JWT má oddelený key, krátke TTL a povinné claim validácie.
- [ ] Session + CSRF + 2FA admin flow zostáva bez regresie.
- [ ] SK/EN API a security dokumentácia je aktualizovaná.

## Súvisiace

[Hybrid Engine security](architecture/HYBRID_ENGINE.md) · [developer security](developer/SECURITY.md)
