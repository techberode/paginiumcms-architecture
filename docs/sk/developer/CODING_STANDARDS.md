---
title: Coding standards
description: Záväzné PHP, TypeScript, architektonické, bezpečnostné a dokumentačné pravidlá PaginiumCMS
icon: material/code-braces
---

# PaginiumCMS — Coding standards

> **Kanonický dokument.** Platí pre jadro, moduly, HTTP vrstvu, frontend, extensions, témy a kód spracovaný cez Code Editor. `CodePolicyEngine` je implementovaná ochranná vrstva; nie je iba budúci plán ani plnohodnotný sandbox.

Detailné pravidlá nedôveryhodného extension kódu sú v [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md). Pri konflikte má prísnejšie bezpečnostné pravidlo prednosť.

## 1. Úrovne pravidiel

| Úroveň | Kde sa vynucuje | Príklady |
|---|---|---|
| Architektonický invariant | návrh, review, testy | flat-file SSOT, extensions mimo Core |
| Statická kvalita | CI/lokálny gate | PHPStan L8, TypeScript, ESLint |
| Write/import policy | backend runtime | cesta, syntax, security scan, manifest, veľkosť |
| Runtime security | middleware/služby | RBAC, CSRF, SSRF guard, rate limit |
| Prevádzková politika | deploy/ops | HTTPS, docroot, secrets, backup |

Žiadna jedna vrstva nenahrádza ostatné. Kód, ktorý prešiel scannerom, môže byť stále logicky zraniteľný; zelený unit test neznamená správne proxy nastavenie.

## 2. Architektonické zákony

### 2.1 Flat-file SSOT

Autoritatívny stav je v schválených súborových úložiskách. Zakázané je:

- zaviesť SQL ako povinný content source,
- zapisovať iba do Redis/indexu a súbor aktualizovať „neskôr“,
- považovať Git remote za jedinú kópiu,
- ukladať secret iba do frontendového storage.

Index, cache, Git mirror, search, preklad a AI artefakty sú odvodené alebo následné vrstvy.

### 2.2 Tenké Core

`backend/app/Core/` obsahuje iba stabilné platformové primitives. Doménové schopnosti patria do `Modules/`; HTTP adaptéry, middleware a external extensions do `Http/`.

Externý plugin:

```text
backend/app/Http/Extensions/{plugin-id}/
backend/app/Http/Routes/extensions/{plugin-id}.php
frontend/src/extensions/{plugin-id}/
```

Plugin kód nikdy nepatrí do `Core/`, `bootstrap/` alebo `vendor/`.

### 2.3 API ↔ frontend kontrakt

Používateľsky dostupný endpoint má typovaný klient, consumer, dokumentáciu a test. Explicitný server-only endpoint je povolený, ale musí byť označený.

### 2.4 Politika pred zápisom/importom

Nedôveryhodný alebo administrátorsky upravovaný kód prechádza:

```text
PATH → SIZE/TYPE → SYNTAX → SECURITY → COMPATIBILITY/MANIFEST
→ backup → atomic write/import → audit
```

Pri zlyhaní sa cieľový súbor alebo balík neaktivuje. HTTP odpoveď má používať `422` pre obsahovú/policy chybu a `403` pre nepovolenú cestu/akciu podľa konkrétneho kontraktu.

## 3. PHP backend

### 3.1 Základ

| Pravidlo | Požiadavka |
|---|---|
| Runtime | PHP 8.5+ podľa aktuálneho projektu |
| Strict types | `declare(strict_types=1);` v každom novom súbore |
| Typy | parameter, return a properties typované; minimalizovať mixed |
| Analýza | PHPStan level 8, bez baseline skrývajúcej nové chyby |
| Štýl | čitateľný PSR-12 kompatibilný formát podľa existujúceho repozitára |
| DI | constructor injection alebo schválená factory/config registrácia |
| I/O | cez repository/driver abstractions, nie náhodné globálne helpery |
| Čas | UTC v persistovaných dátach; timezone až pri prezentácii |
| Identifikátory | stabilné ID/slug validované a kanonikalizované |

Povinná hlavička:

```php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Example;
```

### 3.2 Triedy a zodpovednosť

- controller rieši HTTP adaptáciu,
- application service koordinuje use case,
- domain service obsahuje doménové pravidlo,
- repository rieši persistenciu a concurrency,
- driver/provider izoluje externý systém,
- DTO/value object nesie validovaný tvar,
- middleware rieši cross-cutting HTTP policy.

God service s filesystemom, HTTP klientom, autorizáciou, HTML sanitizáciou a mailom v jednej triede sa nerozdeľuje „niekedy neskôr“ — nerozšíri sa o ďalšiu zodpovednosť.

### 3.3 Errors a výnimky

- používaj doménovo pomenované výnimky alebo result typ podľa existujúceho modulu,
- nechyť `Throwable` iba preto, aby sa vrátil `success: false`,
- neočakávané chyby nech spracuje centrálny error handler,
- produkčná odpoveď neobsahuje stack trace, absolútnu cestu ani secret,
- log obsahuje koreláciu, nie token/cookie.

### 3.4 Filesystem

- cesta sa skladá z kanonických segmentov, nie priamo z user inputu,
- `realpath`/root boundary overenie pri čítaní existujúceho súboru,
- zákaz `..`, NUL, absolútnych ciest a escape cez symlink,
- zápis cez temp file + bezpečný rename,
- koordinácia cez existujúci lock/OCC kontrakt,
- pri JSON používať predvídateľné encoding flags a kontrolu chyby,
- nikdy nepredpokladať, že rename alebo chmod funguje rovnako na každom volume bez testu.

### 3.5 Bezpečné outbound volania

- URL validuje schválený guard,
- v produkcii preferovať HTTPS,
- blokovať loopback/private/link-local/metadata rozsahy podľa policy,
- redirecty znovu validovať,
- timeout, limit veľkosti a počet redirectov sú povinné,
- tajomstvá nejdú do query stringu alebo logu,
- provider response je nedôveryhodný vstup.

## 4. TypeScript a React

### 4.1 Typy

- `strict` zostáva zapnutý,
- `any` v exportovanom API je zakázané; použi `unknown` + validáciu,
- response typ nemá predstierať povinné pole, ktoré backend negarantuje,
- diskriminované union typy preferuj pre stavové workflow,
- enum/string union musí zodpovedať backendovej schéme.

### 4.2 API moduly

```typescript
export type Example = {
  id: string;
  revision: string;
};

export const exampleApi = {
  get: (id: string) => apiClient.get<Example>(`/api/examples/${encodeURIComponent(id)}`),
};
```

Pravidlá:

- URL segment kóduj,
- query vytváraj cez `URLSearchParams` alebo spoločný helper,
- session token neukladaj do `localStorage`,
- mutácie používajú spoločný CSRF flow,
- endpoint modul exportuj cez barrel podľa projektu,
- UI nepoužíva raw request, ak existuje typovaný modul.

### 4.3 React komponenty

- komponent má jednu zrozumiteľnú zodpovednosť,
- side effects patria do `useEffect` iba ak ide o synchronizáciu s externým systémom,
- dependency array sa neopravuje vypnutím lint pravidla bez dôvodu,
- async operácia rieši unmount/cancel alebo ignorovanie stale odpovede,
- formulár zobrazí field errors z `422`,
- `409` konflikt má vlastný UX, nie generic toast,
- ne-JSON `403` sa spracuje bezpečne.

### 4.4 Accessibility a i18n

- interaktívny element je `button`/`a`, nie klikateľný `div`,
- label, focus order a keyboard workflow sú súčasť Definition of Done,
- farba nie je jediný nositeľ stavu,
- UI text cez i18n kľúč,
- dátum/číslo formátuj podľa locale, persistuj v stabilnom formáte,
- content locale a UI locale zostávajú oddelené.

### 4.5 Bezpečný rendering

- žiadne `dangerouslySetInnerHTML` bez centrálnej sanitizácie,
- URL scheme allowlist,
- externý link podľa policy používa bezpečné `rel`,
- SVG/upload nie je automaticky dôveryhodný,
- Markdown renderer nesmie povoľovať raw HTML bez jasnej sanitizácie.

## 5. Názvy a štruktúra

| Prvok | Konvencia | Príklad |
|---|---|---|
| PHP class/interface | PascalCase | `ContentRepository` |
| PHP method/property | camelCase | `findPublished()` |
| TS type/component | PascalCase | `ContentEditor` |
| TS function/variable | camelCase | `loadContent()` |
| Constant | existujúca projektová konvencia, typicky UPPER_SNAKE_CASE | `MAX_UPLOAD_BYTES` |
| Route path | lowercase kebab/segment podľa existujúceho API | `/api/admin/content-meta` |
| Plugin ID | `a-z0-9-`, kebab-case | `weather-widget` |
| JSON key | stabilná existujúca schéma, typicky camelCase | `updatedAt` |
| Test | názov správania, nie implementačného detailu | `rejects stale revision` |

Skratky používaj konzistentne: `ApiClient` alebo projektový existujúci tvar, nie mix `APIClient`, `ApiCLIENT` a `api_client`.

## 6. CodePolicyEngine a write-time gate

### 6.1 Čo engine robí

Podľa povoleného kontextu môže overovať:

- root/path allowlist a traversal,
- typ a veľkosť súboru,
- PHP/JSON/YAML syntax,
- zakázané PHP tokeny/funkcie,
- extension namespace a manifest,
- špecifické untrusted artefakty, napríklad shortcode/layout definície.

### 6.2 Čo engine nerobí

- nedokazuje absenciu business-logic zraniteľnosti,
- neizoluje nekonečnú slučku, memory bomb alebo side channel,
- negarantuje bezpečný third-party JS bundle,
- nenahrádza OS/container sandbox,
- nenahrádza review závislostí a capability model.

### 6.3 Typické zakázané konštrukty extension kódu

```text
eval, shell_exec, exec, system, passthru, proc_open,
dynamický include/require mimo vlastného rootu,
nebezpečný unserialize,
priame čítanie .env alebo data/users,
priamy outbound klient obchádzajúci guard,
symlink/absolútna cesta/traversal v balíku
```

Konkrétny zoznam v implementácii a settings je zdroj pravdy. Výnimka nesmie byť tichý wildcard; musí mať ownera, dôvod, minimálny scope, audit a expiry/review.

## 7. Extensions a témy

Manifest minimálne identifikuje ID, názov, verziu a kompatibilitu. Import workflow:

```text
upload to temporary location
→ archive limits + Zip-Slip/symlink checks
→ manifest/schema
→ scan every allowed file
→ stage files
→ registry record disabled
→ explicit enable
```

Frontend zdroje v extension balíku sú build-time. Aktivácia backend pluginu nesmie tvrdiť, že dynamicky načítala ľubovoľný nový React bundle.

Téma/farebná schéma a executable plugin nie sú rovnaká úroveň dôvery. Preferuj deklaratívne tokeny a schémy pred vykonateľným kódom.

## 8. Testovacie štandardy

### 8.1 Backend

- Arrange/Act/Assert alebo rovnako čitateľný tvar,
- každý test izoluje filesystem root,
- test neporovnáva nestabilný timestamp bez kontroly hodín,
- concurrency test má deterministický trigger/barrier, nie náhodný `sleep`,
- security test používa neškodný payload,
- HTTP test overí status, body contract a side effect.

### 8.2 Frontend

- testuj používateľsky pozorovateľné správanie,
- mockuj transport na hranici API modulu, nie interný detail každého hooku,
- testuj loading/error/empty a conflict branch podľa rizika,
- snapshot nie je náhrada za assertion významu,
- timeouts nezvyšuj ako prvú opravu flaky testu.

### 8.3 Regresie

Každý bug fix má test, ktorý:

1. bez fixu zlyhá,
2. s fixom prejde,
3. pomenúva pôvodný vektor,
4. neobsahuje reálne secret alebo škodlivú produkčnú akciu.

## 9. Dokumentácia a komentáre

Komentár vysvetľuje **prečo**, invariant alebo neintuitívnu hranicu. Neopisuje mechanicky nasledujúci riadok.

Verejný kontrakt dokumentuj tam, kde ho nájde ďalší vývojár:

- API v architektúre,
- storage schema v storage/versioning,
- extension capability v plugin policy,
- ops requirement v deploy docs,
- incident v `ISSUES.md` a changelogu.

Statusy `implementované`, `prechodné`, `plánované` nepomiešaj.

## 10. Security checklist pred merge

- [ ] backend permission na každej chránenej route,
- [ ] CSRF na session mutácii alebo zdokumentovaná úzka výnimka,
- [ ] path/URL/file input kanonikalizovaný,
- [ ] upload/archive má limity a typovú validáciu,
- [ ] output/rendering je kontextovo escapovaný alebo sanitizovaný,
- [ ] secret sa nezobrazuje, neloguje a nejde do URL,
- [ ] audit/log data sú chránené proti CR/LF/CSV injection,
- [ ] rate limit a abuse model sú posúdené,
- [ ] retry/idempotency pri jobe alebo externom provideri,
- [ ] rollback a backup pri zápise/migrácii,
- [ ] regresný test pre security fix.

## 11. Príkazy quality gate

```bash
composer test
composer stan
composer cs
composer audit

cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

Celý projekt:

```bash
./scripts/iteration-gate.sh
```

Aktuálny CI workflow a package scripts sú autoritatívne. Počet testov nie je coding standard; nulové relevantné zlyhania a zmysluplné pokrytie áno.

## 12. Výnimky a zmena štandardu

Výnimka musí obsahovať:

- presné pravidlo,
- dôvod,
- dotknuté súbory/capability,
- riziko a mitigáciu,
- ownera,
- dátum alebo podmienku revízie.

Zásadná zmena tohto dokumentu potrebuje architektonické review a synchronizáciu SK/EN. Tiché vypnutie lintu/scannera v jednom module nie je zmena štandardu — je to obchádzka.

Súvisiace: [CONTRIBUTING.md](CONTRIBUTING.md), [DEVELOPMENT.md](DEVELOPMENT.md), [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md), [SECURITY.md](SECURITY.md), [API_CONTRACT.md](../architecture/API_CONTRACT.md).
