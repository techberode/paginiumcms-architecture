---
title: Hardening jadra
description: Bezpečnostné a prevádzkové invarianty PaginiumCMS
icon: material/shield-lock-outline
---

# Hardening jadra PaginiumCMS

> **Checkpoint:** `v2.1.0-beta.23`  
> **Pravidlo:** bezpečnostná kontrola je účinná iba vtedy, keď je vynútená na serveri a testovaná.

Tento dokument konsoliduje ochrany jadra a rozširuje ich pre Hybrid Engine smer. Nie je náhradou threat modelu ani deployment hardeningu; určuje minimálne invarianty, ktoré nesmie porušiť controller, modul, driver, extension ani externý klient.

---

## 1. Request pipeline

Aktuálny bootstrap dokumentuje približné poradie:

```text
CORS / security headers
→ maintenance + locale
→ WAF
→ global rate limit
→ analytics/request logging
→ route auth
→ role/permission/2FA/developer gates
→ controller/domain validation
→ unified error handler
```

Skutočné Slim middleware poradie môže byť ovplyvnené LIFO správaním; preto musí existovať integračný test, ktorý overí výsledné poradie, nie iba poradie `add()` volaní.

WAF je pred rate limiterom podľa architektonického zámeru, ale ani jedna vrstva nenahrádza permission a schema validation.

---

## 2. Autentifikácia

### Administrácia

Aktuálnym primárnym modelom je serverová session, secure cookie, CSRF synchronizer token a TOTP pre chránené oblasti. Pri login sa regeneruje session ID.

Povinné vlastnosti:

- `HttpOnly`, `Secure` v HTTPS produkcii a vhodné `SameSite`,
- session fixation ochrana,
- timeout a explicitný logout/invalidation,
- login rate limit a audit bez hesla/TOTP,
- recovery/2FA flow s rovnakou úrovňou ochrany,
- žiadne session alebo CSRF tokeny v URL.

### Headless klienti

It.74 pridá API keys a krátko žijúce JWT ako aditívny model. Tokeny musia mať scopes, expiry, rotáciu, revokáciu a audit. Browser admin nesmie prejsť na dlhodobý Bearer token v `localStorage` iba kvôli zjednodušeniu implementácie.

---

## 3. Autorizácia

Každá mutácia kontroluje permission v doméne, nie iba viditeľnosť tlačidla. Orientačne:

| Operácia | Permission |
|----------|------------|
| create content | `content:create` |
| edit content | `content:edit` |
| delete content | `content:delete` |
| upload/delete media | `media:upload` / `media:delete` |
| firewall/log/settings admin | admin/restricted permission |
| access control zmena | SUPER_ADMIN alebo dedicated permission |
| developer code write | developer unlock + code policy + permission |

`ADMIN` manage permission môže pokryť doménové akcie, ale implementácia musí používať jeden permission resolver. Driver alebo background job dostane actor/service identity a nesmie bežať ako neobmedzený „system superuser“ bez scope.

Path ACL sa uplatní nad canonical content key/path a musí mať rovnaké správanie v UI, REST API, queue aj restore.

---

## 4. CSRF, CORS a cookies

- Session-auth mutácie vyžadujú CSRF token.
- Bearer klient bez cookies nepoužíva CSRF, ale stále prechádza scopes/rate limitom.
- Produkčný CORS je explicitný allow-list, nie reflexia ľubovoľného `Origin`.
- Preflight nesmie sprístupniť chránené credentials originom mimo policy.
- Cookie domain/path sa drží čo najužšie.
- `Access-Control-Allow-Credentials` sa nepoužíva s wildcard origin.

---

## 5. Input, schema a output

Všetky vstupy majú size limit, typový kontrakt a normalizáciu. Kľúčové domény:

- slug/path a názvy súborov,
- Markdown/front matter/JSON Schema,
- URL pre webhook, import, stock media, Git a provider API,
- MIME/extension/veľkosť uploadu,
- locale, timezone, enumy a retention,
- pagination a filter bounds.

Output encoding závisí od kontextu. Markdown render používa sanitizáciu; logy používajú CR/LF/ANSI sanitizáciu; CSV export chráni formula injection; JSON chyby neobsahujú stack trace v produkcii.

---

## 6. Storage a media hardening

- allow-list storage roots,
- path traversal a symlink escape protection,
- atomic write a safe permissions,
- web deny pre `data`, logs, backups, cache, firewall a dev stores,
- HTML/SVG media ako attachment alebo sandbox policy,
- žiadne PHP/executable uploady do web-served path,
- private media URL policy v It.72,
- checksum a journal pri migrácii drivera.

Detail: [STORAGE.md](./STORAGE.md).

---

## 7. WAF

`FirewallMiddleware` a služby skenujú scenáre, spravujú bans/SIN score a incidenty. Tok:

1. trusted client IP sa určí iba cez správne nastavené `TRUSTED_PROXIES`,
2. explicitná allow-list sa použije opatrne a auditovane,
3. aktívny ban/jail vráti 403,
4. pattern match vytvorí incident a prípadnú eskaláciu,
5. request pokračuje do ďalších vrstiev iba ak prejde.

WAF pravidlá musia mať testy na false positives a bypass encoding. Neparsujú SQL dotaz, pretože CMS SQL nepoužíva, ale SQLi pattern môže stále indikovať útok na vstupnú plochu.

Test environment môže WAF vypnúť pre väčšinu testov, ale musí existovať samostatná WAF suite.

---

## 8. Rate limiting a abuse controls

Globálny limit, login limiter a per-route limity používajú canonical client identity. Citlivé endpointy majú vlastné budgety:

- login/reset/OTP resend,
- comments/contact/register,
- import/upload,
- translation/AI/provider calls,
- API key creation a token exchange,
- expensive search/rebuild/admin export.

Rate-limit store je odvodený prevádzkový stav. Výpadok Redis nesmie vytvoriť neobmedzený režim bez incidentu; fallback policy musí byť explicitná a vhodná pre deployment profil.

---

## 9. Maintenance a dostupnosť

Maintenance mode vracia 503 pre verejné API podľa policy, ale ponecháva health/auth/admin recovery cesty potrebné na opravu. Výnimky majú byť allow-list, nie široký prefix, ktorý otvorí nový endpoint omylom.

Prihlásený staff preview môže fungovať, ale verejná cache nesmie uložiť staff-only response.

---

## 10. Tajomstvá

- citlivé settings polia sú šifrované at rest,
- master key nie je v repozitári ani settings JSON,
- token comparison používa constant-time funkcie,
- credential sa nezobrazuje po uložení,
- webhook/API/provider logs redigujú headers a query secrets,
- backup a restore dokumentujú key dependency,
- rotácia má versioned ciphertext a rollback,
- Git commit/publish nikdy nepridá `.env`, private keys alebo storage secrets.

---

## 11. Outbound a SSRF

Každý outbound connector, webhook, Git callback, media import, translation alebo AI provider používa spoločnú URL policy:

- povolené `https` a explicitne zdokumentované výnimky,
- DNS/IP kontrola proti loopback, link-local, private a metadata sieťam podľa policy,
- redirect sa validuje pri každom hop-e,
- timeout, response size a content type limit,
- credentials sa posielajú iba očakávanému hostu,
- audit provider/host bez secret query.

Self-hosted provider môže potrebovať private LAN adresu; táto výnimka musí byť explicitná admin allow-list, nie automatické vypnutie SSRF ochrany.

---

## 12. Developer Mode a extension code

Developer Mode je locked-by-default aj pri debug config. Unlock vyžaduje TOTP alebo offline dev token, má TTL a audit. Code editor:

- zapisuje iba do povolených rootov,
- robí backup/diff,
- spúšťa syntax a `CodePolicyEngine`,
- blokuje nebezpečné konštrukty podľa policy,
- neumožňuje extension volať shell alebo generic filesystem API,
- po Apply nepovažuje kód automaticky za publikovaný/deploynutý.

Import ZIP chráni pred Zip-Slip, limituje počet/veľkosť súborov a validuje manifest pred presunom do runtime rootu.

---

## 13. Events, queue a background jobs

Job nesie actor/service identity, idempotency key, minimal payload a deadline/retry policy. Secrets sa referencujú bezpečným ID, nie kopírujú do queue JSON.

Listener/job nesmie obísť permission, path validation alebo schema iba preto, že neprišiel cez HTTP. Poison job má dead-letter/incident stav; nekonečný retry je zakázaný.

`after_save` failure po úspešnom zápise nesmie deklarovať rollback. Publish/translation/AI stavy sa evidujú samostatne.

---

## 14. Logging a audit

| Vrstva | Úloha |
|--------|-------|
| HTTP access log | method, route template, status, duration, request ID, redacted client context |
| Application log | technická chyba a incident ID |
| Security log | auth/WAF/rate/permission udalosti |
| Audit trail | kto, čo, nad akým objektom, pred/po metadata bez secrets |
| Event/job log | event/job ID, handler, outcome, retry |

Log injection sa sanitizuje. Retention, rotation a permission sú settings/deployment policy. Auth body, cookies, Authorization header, TOTP, reset token a plaintext credentials sa nelogujú.

---

## 15. Soft delete, restore a destructive actions

Trash operácie validujú origin path a konflikt. Bulk restore/purge má count limit, permission a audit. Permanent purge, key rotation, driver migration, extension install a access-control zmena vyžadujú explicitné potvrdenie; pri najcitlivejších workflow môže platiť OTP approval.

---

## 16. Hybrid Engine bezpečnostné gates

| Iterácia | Povinný gate |
|----------|--------------|
| It.68 | driver parity, path tests, schema validation, migration journal |
| It.69 | cache poisoning/tenant-locale keys, Redis TLS/auth, safe fallback |
| It.70 | Git command safety, secret exclusion, signed/controlled remote policy, idempotent publish |
| It.71 | metriky bez PII/secrets, self-heal iba allow-listed a vratný |
| It.72 | private/public media policy, checksum migration, SSRF pre import |
| It.73 | locale ACL/revision integrity a fallback bez data leak |
| It.74 | scoped keys/JWT, rotation, revocation, no browser localStorage |
| It.75 | prompt injection isolation, allow-listed schema tools, human Apply, no autonomous publish |
| It.76–77 | proposal/diff/Apply, quota, secret provider config, no auto-publish |

---

## 17. Testovací gate

- auth/session/CSRF fixation a expiry,
- RBAC/path ACL pre HTTP, queue a restore,
- WAF bypass/false-positive testy,
- rate limit a trusted proxy spoofing,
- path traversal/symlink/Zip-Slip/upload polyglot,
- SSRF redirect/DNS rebinding scenáre podľa možností test harnessu,
- secret redaction v API/log/export,
- broken/readonly/disk-full storage,
- extension/AI tool sandbox boundaries,
- Classic smoke test bez externých capabilities,
- dependency audit, PHPStan L8, PHPUnit, TypeScript, ESLint a Vitest.

---

## Súvisiace dokumenty

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [CORE.md](./CORE.md)
- [STORAGE.md](./STORAGE.md)
- [SETTINGS.md](./SETTINGS.md)
- [EVENTS.md](./EVENTS.md)
- [API_CONTRACT.md](./API_CONTRACT.md)
- [../user/FIREWALL.md](../user/FIREWALL.md)
- [../user/LOGGING.md](../user/LOGGING.md)
