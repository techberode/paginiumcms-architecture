---
title: Vývoj PaginiumCMS
description: Každodenný vývojový workflow, mapa repozitára, debugging a bezpečné zmeny platformy
icon: material/laptop
---

# Vývoj PaginiumCMS

> Tento dokument vysvetľuje, **ako na projekte pracovať každý deň**. Inštaláciu prostredia rieši [LOCAL_SETUP.md](LOCAL_SETUP.md), pravidlá príspevku [CONTRIBUTING.md](CONTRIBUTING.md) a detailný štýl [CODING_STANDARDS.md](CODING_STANDARDS.md).

## 1. Vývojový mentálny model

```text
React/Vite admin SPA
        ↓ typed API client
Slim HTTP routes + middleware
        ↓
application/domain services
        ↓
flat-file repositories (SSOT)
        ↓
derived index/cache/audit/jobs
```

Pri každej zmene si polož štyri otázky:

1. Ktorá vrstva je autoritatívna?
2. Kde sa overuje oprávnenie a vstup?
3. Čo sa stane pri prerušení zápisu alebo páde následného jobu?
4. Ako sa zmena otestuje a vráti späť?

## 2. Mapa repozitára

Typická štruktúra:

```text
backend/
  app/Core/                  platformové primitives
  app/Modules/               doménové moduly
  app/Http/                  routes, controllers, middleware, extensions
  bootstrap/                 zostavenie aplikácie
  public/                    jediný produkčný PHP docroot
  storage/                   runtime a autoritatívne flat-file dáta
  tests/                     PHPUnit testy
frontend/
  src/api/                   typované API moduly
  src/components/            React UI
  src/extensions/            build-time frontend extension zdroje
  src/hooks|context|utils/    zdieľaná aplikačná vrstva
scripts/                     first-run, gates, maintenance helpers
docs/                        architektúra, user a developer príručky
```

Presná štruktúra konkrétneho tagu má prednosť. Nový priečinok nevytváraj iba preto, že podobný framework ho bežne používa; musí mať jasné vlastníctvo a dependency smer.

## 3. Odporúčaný denný cyklus

```bash
git switch main
git pull --ff-only
git switch -c fix/iss-123-example

# baseline pred zmenou
composer test
composer stan
cd frontend && npm run type-check && npm test -- --run
```

Potom:

1. reprodukuj problém alebo zapíš acceptance kritériá,
2. pridaj test, ktorý zlyhá správnym dôvodom,
3. implementuj najmenšiu konzistentnú zmenu,
4. spusti úzky test počas práce,
5. spusti celý relevantný gate,
6. aktualizuj dokumentáciu a issue/changelog,
7. skontroluj diff vrátane náhodných runtime súborov.

Užitočné príkazy:

```bash
git status --short
git diff --check
git diff --stat
git diff -- backend/app/Modules frontend/src/api docs/
```

## 4. Backend workflow

### 4.1 Route a middleware

Route súbor má deklarovať iba HTTP mapovanie a middleware. Typický tok:

```text
route
→ authentication
→ role/permission
→ CSRF pri session mutácii
→ rate limit / domain guard podľa rizika
→ controller
```

Slim middleware môže mať LIFO správanie podľa spôsobu registrácie. Pri bezpečnostnej zmene nespoliehaj na vizuálne poradie v súbore; pridaj integračný test, ktorý dokáže skutočné runtime poradie.

### 4.2 Controller a aplikačná služba

Controller:

- načíta a normalizuje HTTP vstup,
- zavolá aplikačnú službu,
- vráti responder/envelope,
- nerieši ručne `flock`, adresáre, šifrovanie alebo doménový merge.

Aplikačná služba:

- vynucuje doménový workflow,
- používa autorizovaný kontext používateľa,
- koordinuje repository, audit a následné udalosti,
- rozlišuje primárny úspech SSOT od zlyhania odvodeného kroku.

### 4.3 Repository a storage

Pri zápise používaj existujúce abstractions. Bezpečný základ:

```text
validate → canonicalize path → acquire lock
→ read current revision → write temp file
→ fsync/close podľa platformy → atomic rename
→ release lock → version/audit/index/cache event
```

Pri multi-file operácii dokumentuj, či ide o transakčný manifest, compensating action alebo best-effort workflow. Flat-file neznamená „bez pravidiel“; práve naopak, filesystem vie byť veľmi úprimný učiteľ.

### 4.4 Výnimky a error kódy

- validácia: `422`,
- neprihlásený používateľ: `401`,
- nedostatočné oprávnenie alebo WAF/CSRF: typicky `403`,
- revision/lock conflict: `409`,
- rate limit: `429`,
- neočakávaná chyba: bezpečný `5xx` bez stack trace v produkcii.

Klientom riešiteľné chyby majú stabilný `code`, ak ho daný kontrakt už podporuje. Neodhaľuj absolútnu cestu, secret ani interný exception detail.

## 5. Frontend workflow

### 5.1 API klient

Všetky štandardné volania patria do typovaných modulov:

```typescript
// src/api/example.ts
export const exampleApi = {
  list: () => apiClient.get<ExampleListResponse>('/api/examples'),
};
```

Komponent nemá roztrúsiť URL, CSRF retry a envelope parsing po UI. Extension môže mať vlastné izolované `api.ts`, ale používa spoločný bezpečný klient.

### 5.2 Server state a UI state

Backend je SSOT. Rozlišuj:

- server state: content, user, settings, locks, revisions,
- lokálny UI state: otvorený panel, neodoslaný draft formulára, filter,
- persisted client preference: iba bezpečný namespaced údaj, nikdy token alebo secret.

Optimistická aktualizácia musí vedieť vrátiť stav pri `409`, `422` alebo network failure.

### 5.3 Editor lifecycle

Pri content editore myslí na:

```text
load document + revision
→ acquire/refresh lock
→ local edits / autosave draft
→ explicit save with expected revision
→ resolve 409 conflict
→ release lock
```

Budúce preklady, Git publish a AI Apply sú samostatné operácie. Tlačidlo Save ich nesmie vykonať potichu.

### 5.4 i18n a obsahové locale

Admin UI i18n prekladá rozhranie. Content locale z It.73 je samostatný dokumentový model. Nespájaj ich do jedného globálneho `language` prepínača.

## 6. Spúšťanie testov počas práce

Backend úzky test:

```bash
./vendor/bin/phpunit --filter ExampleServiceTest
./vendor/bin/phpstan analyse --level=8 backend/app/Modules/Example
```

Frontend úzky test:

```bash
cd frontend
npm test -- --run src/api/example.test.ts
npm run type-check
```

Pred PR:

```bash
composer gate
# alebo
./scripts/iteration-gate.sh
```

Rozšírený runner môže zahŕňať build, audit, smoke kolekciu, diagnose a ďalšie kroky. Presný zoznam čítaj z aktuálneho skriptu.

## 7. Lokálne dáta a testovacia izolácia

- Necommituj `backend/storage` runtime obsah, ak nejde o úmyselný fixture.
- Nepoužívaj produkčný export používateľov ako testovacie dáta.
- Fixtures minimalizuj a odstráň osobné údaje.
- Každý test dostane vlastný dočasný koreň alebo unikátny namespace.
- Po paralelných testoch nezdieľaj jeden lock/index súbor bez koordinácie.
- `APP_ENV=testing` a explicitné test env overrides musia zabrániť načítaniu lokálneho `DEMO_MODE=true` alebo produkčných secrets.

Pri poškodenom lokálnom indexe najprv diagnostikuj autoritatívny storage, až potom rebuildni odvodenú vrstvu:

```bash
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --fix
```

## 8. Debugging

### 8.1 HTTP chyba

Zaznamenaj:

- method + path,
- status a `content-type`,
- request/correlation ID, ak je dostupné,
- rolu/permission bez zverejnenia session,
- CSRF stav pri mutácii,
- serverový log a audit event.

Frontend nesmie predpokladať JSON pri každom `403`; WAF alebo reverse proxy môže vrátiť text/HTML/prázdne telo.

### 8.2 Auth/session problém

Skontroluj cookie flags, origin, HTTPS, proxy headers, `TRUSTED_PROXIES`, čas systému a to, či session storage prežije medzi requestmi. `SESSION_STRICT=false` môže byť lokálna proxy kompatibilita, nie univerzálne produkčné odporúčanie.

### 8.3 Storage problém

Over:

```bash
find backend/storage -maxdepth 3 -type d -printf '%m %u:%g %p\n'
df -h
df -i
```

Nepoužívaj `chmod -R 777`. Oprav vlastníka a najmenší potrebný mód pre používateľa PHP-FPM/kontajnera.

### 8.4 Frontend build problém

```bash
cd frontend
rm -rf node_modules
npm ci
npm run type-check
npm run build
```

Ak sa zmenil frontend extension zdroj, rebuild je očakávaný. PHP aktivácia pluginu sama nevytvorí nový Vite bundle.

## 9. Jobs, Git, cache a externé providery

Tieto schopnosti sú následné vrstvy:

- lokálny SSOT write musí mať vlastný výsledok,
- job má stabilný payload, identity/permission snapshot a idempotency pravidlo,
- retry nesmie vytvárať duplicitné commity, preklady alebo notifikácie,
- outbound URL prechádza SSRF guardom,
- secret sa načíta server-side a nerediguje iba „na oko“ vo FE,
- zlyhanie Redis/Git/provideru je viditeľné a obnoviteľné.

Schopnosti It.68–77 implementuj až podľa schválených kontraktov v príslušných dokumentoch.

## 10. Vývoj extensions a tém

Backend extension:

```text
backend/app/Http/Extensions/{plugin-id}/
backend/app/Http/Routes/extensions/{plugin-id}.php
```

Frontend extension:

```text
frontend/src/extensions/{plugin-id}/
```

Každý import a zápis nedôveryhodného extension kódu prechádza policy gate. Aktivácia, frontend build a deploy sú oddelené. Pri väčšom riziku potrebujeme process/container izoláciu; `CodePolicyEngine` nie je VM.

## 11. Dokumentačný workflow

Pri zmene kontraktu aktualizuj minimálne:

| Zmena | Dokument |
|---|---|
| endpoint/response | `architecture/API.md`, `API_CONTRACT.md` |
| storage/schema | `architecture/STORAGE.md`, `VERSIONING.md` |
| nový modul/event | `architecture/MODULES.md`, `EVENTS.md` |
| extension pravidlo | `PLUGINS.md`, `EXTENSION_CODE_POLICY.md` |
| používateľské workflow | príslušný `user/*.md` |
| release/incident | `CHANGELOG.md`, `ISSUES.md`, release notes |

SK a EN vetvu udržuj významovo zhodnú. Preklad nemusí byť slovo za slovom, ale nesmie meniť status, bezpečnostnú hranicu ani acceptance kritérium.

## 12. Definition of Done

Zmena je hotová, keď:

- implementácia rešpektuje architektúru a bezpečnostné invarianty,
- testy pokrývajú úspech aj relevantnú failure vetvu,
- lokálny gate a CI sú zelené,
- migrácia/rollback sú overené, ak sa menia dáta,
- API a frontend sú zosúladené,
- dokumentácia neklame o stave,
- diff neobsahuje secrets alebo runtime odpad,
- reviewer vie zreprodukovať výsledok.

Súvisiace: [CONTRIBUTING.md](CONTRIBUTING.md), [LOCAL_SETUP.md](LOCAL_SETUP.md), [CODING_STANDARDS.md](CODING_STANDARDS.md), [TESTING.md](TESTING.md), [BETA_INFRA.md](BETA_INFRA.md).
