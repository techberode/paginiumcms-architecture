---
title: Prispievanie do PaginiumCMS
description: Workflow pre issues, návrhy, kód, testy, dokumentáciu a bezpečné pull requesty
icon: material/source-pull
---

# Prispievanie do PaginiumCMS

## 0. Aktuálny implementačný baseline — Wave 5e / It.17

Tento dokument zostáva všeobecným kontraktom prispievania. Pre aktuálnu vetvu navyše platia tieto konkrétne pravidlá:

```text
route + controller/application service
→ typovaný frontend API modul
→ export v API barreli
→ reálny component/hook/extension consumer
→ API dokumentácia
→ backend + frontend test
```

### Povinný checklist nového alebo zmeneného endpointu

- [ ] Route je registrovaná pod `backend/app/Http/Routes/`; plugin route patrí pod kontrolovaný extension route strom.
- [ ] Controller používa DI, `JsonResponder` a príslušné AuthZ, CSRF, 2FA a Path ACL kontroly.
- [ ] Typovaný klient je v `frontend/src/api/{module}.ts`; extension môže mať vlastné `frontend/src/extensions/{id}/api.ts`.
- [ ] Modul je re-exportovaný z `frontend/src/api/index.ts` a objekt `fooApi` je dostupný aj cez `api.foo`, ak ide o verejný API modul.
- [ ] Existuje reálny consumer; UI nemá obchádzať typovaný klient cez náhodné `apiClient.get('/api/...')`.
- [ ] Endpoint je zapísaný v [API.md](../architecture/API.md) a pri zmene envelope aj v [API_CONTRACT.md](../architecture/API_CONTRACT.md).
- [ ] Zmena má PHPUnit test a pri netriviálnej frontend logike aj Vitest/MSW test.
- [ ] `npm run lint:api-barrel` prejde.

Explicitne zdokumentované server-only CLI, worker, scheduler alebo webhook operácie môžu byť bez frontend consumera. Výnimka však nesmie vzniknúť iba preto, že frontend wiring sa „dorobí neskôr“.

### Aktuálne zákazy

- externý plugin nepatrí do `backend/app/Core/`, ale do kontrolovaného extension priestoru,
- necommitujú sa `.env`, secrets, raw ani sanitizované testovacie logy, `SECURITY_ISSUES.md`, `PRIVATE_OPS_CHECKLIST.md` ani lokálny `LOCAL_TEST_LOGS.md`,
- `LOCAL_TEST_LOGS.md.example` je iba verejná šablóna bezpečného maintainer workflow,
- zmena, ktorá poruší strict types, PHPStan L8, API barrel alebo extension code policy, sa nemerguje.

> Tento dokument je vstupný kontrakt pre príspevky do release rodiny **`v2.1.0-beta.*`**. PaginiumCMS je flat-file platforma smerujúca k **Hybrid Headless Content Engineu**; súbory zostávajú povinným zdrojom pravdy a odvodené vrstvy ho nesmú nahradiť.

Príspevok nemusí byť iba veľký feature. Cenné sú aj reprodukovateľné bug reporty, oprava dokumentácie, test regresie, bezpečnostné hardening pravidlo alebo zmenšenie architektonického dlhu.

## 1. Pred prvou zmenou

1. Prečítaj [filozofiu projektu](../PHILOSOPHY.md), [architektúru](../architecture/ARCHITECTURE.md) a [pokračovanie vývoja](../CONTINUATION.md).
2. Priprav si lokálne prostredie podľa [LOCAL_SETUP.md](LOCAL_SETUP.md).
3. Over stav vetvy cez aktuálny `CHANGELOG.md`, release notes a CI. Historický dokument iterácie nie je automaticky dnešný backlog.
4. Vyhľadaj existujúci záznam v [ISSUES.md](../ISSUES.md) a v otvorených issues repozitára.
5. Pri väčšej zmene najprv spíš návrh: problém, hranice, dátový tok, bezpečnostné dopady, migrácia a rollback.

Nezačínaj veľký refaktor iba preto, že „by sa to dalo krajšie“. Najprv preukáž konkrétny problém alebo merateľný prínos. Staré dobré pravidlo: najprv nech je jasné **prečo**, až potom sa hádajme o názov triedy. 🙂

## 2. Typy príspevkov

| Typ | Očakávaný výstup |
|---|---|
| Bug fix | reprodukcia, regresný test, oprava, záznam v `ISSUES.md`/changelogu podľa významu |
| Feature | návrh kontraktu, backend/FE implementácia, testy, dokumentácia a migračná poznámka |
| Refaktor | nezmenené verejné správanie alebo explicitne popísaná zmena kontraktu |
| Dokumentácia | technicky overiteľný text, platné odkazy a jasné označenie implementované/plánované |
| Bezpečnosť | súkromné nahlásenie podľa koreňového `SECURITY.md`; verejný PR až po koordinácii |
| Extension/theme | manifest, policy scan, kompatibilita, aktivácia/rollback a build poznámka |
| Test infra | deterministické testy, izolované storage a zrozumiteľný failure output |

## 3. Vetva, scope a commity

Odporúčaný názov vetvy:

```text
feat/content-locales
fix/iss-123-lock-race
docs/hybrid-engine-api
security/plugin-zip-hardening
```

Pravidlá scope:

- jedna logická zmena na vetvu a pull request,
- neprimiešavaj hromadné preformátovanie do funkčnej opravy,
- nepresúvaj verejné API alebo storage cesty bez migračného plánu,
- nevkladaj generované runtime dáta, lokálne logy ani privátne ops súbory,
- závislosti aktualizuj oddelene, pokiaľ nie sú nevyhnutné pre danú opravu.

Odporúčaný štýl commitov:

```text
feat(content): add locale-aware document schema
fix(locks): reject stale revision after heartbeat expiry
test(storage): cover interrupted atomic rename
docs(api): mark JWT endpoints as planned
security(plugins): reject symlink entries during import
```

Release commity môže maintainer pomenovať podľa existujúcej konvencie. Bežný contributor nemá vytvárať release tag iba preto, že CI svieti nazeleno.

## 4. Nevyjednateľné architektonické invarianty

| Invariant | Požiadavka |
|---|---|
| Flat-file SSOT | SQL, Redis, index ani Git remote nesmú byť jediným autoritatívnym zdrojom obsahu |
| Atomické zápisy | mutácie používajú existujúci repository/storage kontrakt, lock a bezpečný rename |
| Tenké Core | doménová funkcionalita patrí do modulov; externé extensions nikdy do `Core/` |
| Backendová autorizácia | UI guard nie je bezpečnostná kontrola; každá chránená route overuje rolu/permission |
| CSRF | session mutácie používajú CSRF middleware; výnimka musí byť úzka a zdokumentovaná |
| Secrets | žiadne tajomstvá v Git, URL, logu ani klientskom bundle |
| Odvodené vrstvy | index/cache/Git/preklad/AI zlyhanie nesmie potichu zmeniť SSOT |
| Audit | bezpečnostne alebo doménovo významná mutácia má auditovateľný výsledok |
| Rollback | migrácia, import extension alebo kritické nastavenie musí mať obnoviteľný bod |

Podrobnosti: [CORE.md](../architecture/CORE.md), [STORAGE.md](../architecture/STORAGE.md), [VERSIONING.md](../architecture/VERSIONING.md), [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

## 5. Zákon API ↔ frontend ↔ dokumentácia

Pre používateľsky dostupnú alebo administrátorskú HTTP schopnosť platí:

```text
Route + middleware
    → controller/application service
    → typovaný frontend API modul
    → reálny consumer alebo route
    → API/API_CONTRACT dokumentácia
    → backend + frontend test
```

Checklist novej alebo zmenenej route:

- [ ] route je v `backend/app/Http/Routes/` alebo v schválenej extension route vetve,
- [ ] middleware poradie a permission sú explicitné,
- [ ] controller neobsahuje storage business logiku, ktorú má niesť služba/repository,
- [ ] odpoveď rešpektuje [API_CONTRACT.md](../architecture/API_CONTRACT.md),
- [ ] typovaný klient je v `frontend/src/api/` alebo v izolovanom extension `api.ts`,
- [ ] export je doplnený do API barrel, ak ide o štandardný frontend modul,
- [ ] UI používa typovaný klient, nie roztrúsené raw requesty,
- [ ] endpoint je v [API.md](../architecture/API.md) označený ako implementovaný, prechodný alebo plánovaný,
- [ ] test pokrýva úspech, validáciu a aspoň jednu autorizačnú/chybovú vetvu.

Výnimky existujú pre CLI, scheduler, interný worker, webhook receiver alebo server-only diagnostiku. Výnimka však musí byť **explicitne zdokumentovaná**; „nemá FE, lebo sme naň zabudli“ nie je architektonický profil.

## 6. Backend príspevok

Minimálne pravidlá:

- PHP 8.5+ podľa aktuálneho projektu,
- `declare(strict_types=1);` v každom novom PHP súbore,
- plné typy a PHPStan level 8 bez nových chýb,
- dependency injection namiesto globálneho stavu a ručného vytvárania infra služieb,
- repository/storage API namiesto priameho náhodného zápisu JSON súboru,
- žiadne `include`, shell, `unserialize` alebo outbound URL mimo schváleného kontraktu,
- bezpečné spracovanie ciest, názvov súborov, ZIP položiek a user-controlled URL,
- stabilné error kódy pre klientom riešiteľné chyby.

Pred odovzdaním:

```bash
composer test
composer stan
composer cs
composer audit
```

Ak sa príkazy v konkrétnom release artefakte líšia, rozhoduje `composer.json` a CI workflow, nie starý screenshot v issue.

## 7. Frontend príspevok

- striktný TypeScript; verejný modul nesmie maskovať neznámy tvar cez `any`,
- funkcionálne React komponenty a existujúce projektové konvencie,
- načítanie má stav loading/success/empty/error podľa kontextu,
- mutácia čaká na potvrdenie backendu; optimistický stav má rollback,
- `401`, `403`, `409`, `422`, `429` a ne-JSON odpoveď sa nesmú zliať do jedného „Something went wrong“,
- používateľské texty idú cez admin i18n vrstvu,
- HTML/Markdown renderovanie musí zostať sanitizované,
- navigácia nepoužíva nedôveryhodný `returnTo` bez validácie.

Overenie:

```bash
cd frontend
npm ci
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

## 8. Storage, migrácie a fixtures

Zmena schémy súboru musí popísať:

1. starý a nový tvar,
2. verziu schémy,
3. čítanie starého tvaru počas prechodu,
4. idempotentnú migráciu,
5. backup/rollback,
6. správanie pri prerušení,
7. rebuild odvodeného indexu/cache,
8. test na reálnom fixture aj poškodenom vstupe.

Testy nesmú zapisovať do pracovného produkčného storage. Používaj izolovaný dočasný strom a po teste ho odstráň.

## 9. Extensions, témy a Code Editor

Externý kód patrí do schválených extension/theme ciest a musí prejsť `CodePolicyEngine`. Platí však:

- policy scanner je bezpečnostný gate, nie plný sandbox,
- import ZIP musí odmietnuť traversal, symlink a nepovolené typy,
- aktivácia je samostatná akcia po importe,
- frontend extension cez Vite je build-time bundle; zdroj v ZIP-e automaticky neznamená runtime UI bez buildu,
- `Save` v Code Editore neznamená registráciu, aktiváciu, build, Git push ani deploy.

Detaily sú v [PLUGINS.md](../architecture/PLUGINS.md), [THEMES.md](../architecture/THEMES.md) a [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

## 10. Testy a quality gate

Príspevok musí pridať najnižšiu rozumnú vrstvu testu:

| Zmena | Povinné minimum |
|---|---|
| Service/repository | unit test + failure vetva |
| HTTP route | integračný test statusu, envelope a authz |
| Middleware | allow aj deny scenár |
| React logika | Vitest/Testing Library podľa rizika |
| Storage migrácia | starý fixture, nový fixture, opakované spustenie, rollback |
| Security fix | regresný test pôvodného bypassu |
| Dokumentácia | validné odkazy, párové SK/EN zmeny podľa scope |

Kompletný lokálny gate:

```bash
./scripts/iteration-gate.sh
# alebo projektový rozšírený runner
./scripts/run-all-tests.zsh
```

Runner môže mať environment-dependent kroky. Zlyhanie neignoruj iba preto, že „moja časť prešla“; buď ho oprav, alebo v PR presne dolož, prečo je mimo zmeny a ako bol overený relevantný subset.

## 11. Dokumentácia a stavové tvrdenia

Každá významná zmena aktualizuje príslušný dokument. Dodržuj tri stavy:

- **Implementované** — existuje v kóde a je overiteľné,
- **Prechodné** — schopnosť existuje, ale kontrakt sa konsoliduje,
- **Plánované** — návrh alebo It.68–77 cieľ; nesmie sa písať v prítomnom čase ako hotová funkcia.

Neaktualizuj historický dokument iterácie tak, aby predstieral, že bol vždy iný. Aktuálny stav patrí do roadmapy, changelogu, architektúry alebo nového rozhodnutia; historický záznam môže dostať jasnú poznámku o supersession.

## 12. Pull request checklist

- [ ] problém a cieľ sú vysvetlené,
- [ ] scope je úzky a bez nesúvisiacich zmien,
- [ ] bezpečnostné a storage dopady sú vyhodnotené,
- [ ] migrácia a rollback sú popísané, ak sú potrebné,
- [ ] backend a frontend kontrakt zostali zosúladené,
- [ ] pridané alebo aktualizované testy reálne zlyhajú bez opravy,
- [ ] quality gate je zelený,
- [ ] dokumentácia a changelog/issue záznam sú aktualizované,
- [ ] PR neobsahuje secrets, runtime dáta ani osobné údaje,
- [ ] reviewer vie z popisu zopakovať manuálny smoke test.

## 13. Čo sa nemerguje

- SQL ako nový autoritatívny content store,
- extension kód v `backend/app/Core/`,
- chránená mutácia bez backendovej autorizácie alebo session CSRF,
- priame zapisovanie do indexu/cache namiesto SSOT,
- endpoint bez kontraktu a klienta, pokiaľ nie je zdokumentovaný server-only,
- security fix bez regresného testu,
- závislosť s nevysvetleným auditným nálezom,
- skrytý default účet, hardcoded token alebo demo heslo,
- masívny generovaný diff, ktorý nemožno rozumne reviewovať,
- dokumentácia vydávajúca plán za implementovaný stav.

## 14. Bezpečnostné nálezy a pomoc

Zraniteľnosť nenahlasuj ako verejný issue s exploit detailmi. Použi postup v koreňovom `SECURITY.md`. Do reportu vlož verziu/tag, vektor, dopad, reprodukciu s neškodným payloadom a návrh mitigácie; nikdy neposielaj reálne tajomstvá alebo produkčné osobné údaje.

Pre bežnú pomoc uveď:

- operačný systém a runtime verzie,
- commit/tag,
- použitý deployment profil,
- presný príkaz a exit code,
- redigovaný log,
- minimálnu reprodukciu.

Súvisiace dokumenty: [DEVELOPMENT.md](DEVELOPMENT.md), [LOCAL_SETUP.md](LOCAL_SETUP.md), [CODING_STANDARDS.md](CODING_STANDARDS.md), [TESTING.md](TESTING.md) a [BETA_INFRA.md](BETA_INFRA.md).
