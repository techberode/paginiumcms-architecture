---
title: Developer Mode
description: Odomknutie, session gate, dev tokeny a bezpečné produkčné nastavenie
icon: material/shield-key-outline
---

# Developer Mode — bezpečnostná brána

> Developer Mode odomyká nebezpečné administračné operácie, napríklad Code Editor a vývojárske logy.  
> Nie je to samostatná rola, obídenie RBAC ani povolenie upravovať Core.

---

## 1. Tri podmienky prístupu

```text
feature availability
  + authenticated privileged session
  + valid Developer Mode unlock
  = dočasný prístup ku gated operácii
```

Všetky podmienky sa overujú server-side. Skryté menu alebo React route guard nie sú bezpečnostná kontrola.

---

## 2. Stav gate

```http
GET /api/admin/developer/status
```

Typické polia:

| Pole | Význam |
|------|--------|
| `feature_available` | režim je povolený serverovou konfiguráciou |
| `unlocked` | aktuálna session je odomknutá |
| `unlocked_until` | čas expirácie unlock session |
| `method` | napr. `totp` alebo `token:<label>` |

Status nesmie vracať token hash, `DEV_UNLOCK_SECRET`, TOTP secret ani zoznam citlivých ciest.

---

## 3. Serverová dostupnosť

Príklad vývojového profilu:

```env
APP_ENV=development
APP_DEBUG=true
DEVELOPER_MODE=true
DEV_UNLOCK_SECRET=replace-with-long-random-secret
```

Bezpečný produkčný profil:

```env
APP_ENV=production
APP_DEBUG=false
DEVELOPER_MODE=false
```

Konkrétna precedence `DEVELOPER_MODE`, `APP_DEBUG` a `APP_ENV` musí zostať zhodná s backend implementáciou. Dokumentácia neodporúča povoliť `APP_DEBUG` na produkcii iba kvôli Code Editoru.

Po zmene `.env` reštartuj relevantný PHP worker/container a over status endpoint cez tú istú reverse-proxy cestu, ktorú používa admin SPA.

---

## 4. Odomknutie cez TOTP

```http
POST /api/admin/developer/unlock
Content-Type: application/json

{ "totp_code": "123456" }
```

Požiadavky:

- prihlásená privilegovaná session,
- aktívna 2FA používateľa,
- platný krátko žijúci TOTP kód,
- rate limit/attempt limit,
- CSRF ochrana pri cookie session,
- audit úspechu aj opakovaných zlyhaní.

TOTP unlock nepoužíva kód zobrazený v dokumentácii. Čas servera musí byť synchronizovaný.

---

## 5. Odomknutie cez dev token

```http
POST /api/admin/developer/unlock
Content-Type: application/json

{ "token": "pagdev_…" }
```

Generovanie/registrácia podľa dostupných CLI nástrojov:

```bash
php backend/bin/dev-token.php --label=workstation
php backend/bin/dev-token-register.php
```

Pravidlá dev tokenu:

- tajná hodnota sa zobrazí iba pri vytvorení,
- register uchováva hash, nie plaintext,
- label identifikuje zariadenie/účel,
- jeden token sa nezdieľa medzi ľuďmi a strojmi,
- token sa rotuje po podozrení alebo strate,
- produkcia má preferovať vypnutý Developer Mode,
- token nepatrí do shell history, issue, CI logu ani Git-u.

Ak CLI vytlačí token na terminál, pracuj v bezpečnej session a odstráň ho z clipboard managera po registrácii.

---

## 6. TTL a session scope

Unlock je viazaný na konkrétnu autentifikovanú session a má obmedzenú životnosť; historický default je približne 8 hodín. Presný TTL je runtime policy, nie bezpečnostný sľub dokumentácie.

Unlock sa nesmie prenášať:

- do iného browser profilu,
- na iné zariadenie,
- do API key/JWT identity,
- po logout/password reset/session invalidation,
- na nového používateľa v recyklovanej session.

Citlivá operácia môže vyžadovať nový unlock aj pred všeobecným TTL, napríklad po zmene security contextu.

---

## 7. Ručné zamknutie

```http
POST /api/admin/developer/lock
```

Po práci klikni **Zamknúť editor**. Lock:

- zruší Developer Mode stav session,
- nezruší bežné prihlásenie do CMS,
- zabráni ďalšiemu list/read/write Code Editora,
- má zohľadniť neuložené zmeny na frontende,
- vytvorí auditný záznam.

Zatvorenie karty nie je spoľahlivý lock.

---

## 8. Chránené capability

Implementované alebo dokumentované gated capability:

- Code Editor list/read/save/create/delete/restore,
- vývojárske logy,
- potenciálne budúce extension/theme scaffold a advanced layout authoring.

Developer Mode nesmie automaticky sprístupniť:

- Core, bootstrap alebo vendor zápis,
- shell/terminal,
- arbitrary SQL alebo databázový admin,
- secrets dump,
- bypass WAF/CSRF/RBAC,
- autonomous publish,
- neobmedzený filesystem.

Každá capability má stále vlastnú permission, input validation a policy.

---

## 9. Reverse proxy a session problémy

Ak unlock funguje priamo na backende, ale nie cez verejnú admin URL, skontroluj:

- cookie domain/path/Secure/SameSite,
- `APP_URL` a trusted proxy nastavenie,
- HTTPS termináciu,
- forwarding `Host`/`X-Forwarded-*`,
- CSRF origin kontrolu,
- sticky session alebo shared session storage pri viacerých workeroch,
- server clock pre TOTP.

Do `TRUSTED_PROXIES` nepridávaj celý internet. Použi konkrétny reverse proxy alebo dôveryhodný subnet podľa nasadenia.

---

## 10. Logovanie a incident response

Audituj minimálne:

- actor/user ID,
- unlock metódu bez tajnej hodnoty,
- success/failure a reason code,
- session/request ID,
- timestamp a source IP podľa privacy policy,
- lock a expiry,
- následné gated write operácie.

Pri podozrení:

1. zamkni session a odhlás používateľa,
2. vypni Developer Mode,
3. rotuj `DEV_UNLOCK_SECRET` a dev tokeny,
4. skontroluj audit, Code Editor backups a Git diff,
5. obnov z overenej zálohy, ak bol kód zmenený,
6. spusti security/test gate.

---

## Súvisiace dokumenty

- [Code Editor](CODE_EDITOR.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Bezpečnostná architektúra](../architecture/CORE_HARDENING.md)
- [Nasadenie](../deploy/NGINX_API.md)
